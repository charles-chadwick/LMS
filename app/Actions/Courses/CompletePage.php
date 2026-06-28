<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class CompletePage
{
    /**
     * Record the page as complete for the user, enforcing sequential gating,
     * and stamp course completion when every published page is finished.
     *
     * @return int|null The next incomplete published page id, or null when complete.
     */
    public function execute(User $user, Course $course, Page $page): ?int
    {
        $pages = $course->pages()->published()->get(['id', 'order']);

        abort_unless($pages->contains('id', $page->id), 404);

        $completed_page_ids = UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');

        $target_order = $pages->firstWhere('id', $page->id)->order;

        foreach ($pages as $earlier) {
            if ($earlier->order >= $target_order) {
                break;
            }

            abort_unless($completed_page_ids->contains($earlier->id), 403);
        }

        UserProgress::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'page_id' => $page->id,
        ]);

        $completed_page_ids = UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');

        $this->stampCompletionIfFinished($course, $user, $pages, $completed_page_ids);

        $next = $pages->first(fn (Page $candidate) => ! $completed_page_ids->contains($candidate->id));

        return $next?->id;
    }

    /**
     * Stamp the pivot completed_at the first time all published pages are done.
     *
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, int>  $completed_page_ids
     */
    private function stampCompletionIfFinished(
        Course $course,
        User $user,
        Collection $pages,
        Collection $completed_page_ids
    ): void {
        $all_complete = $pages->isNotEmpty()
            && $pages->every(fn (Page $page) => $completed_page_ids->contains($page->id));

        if (! $all_complete) {
            return;
        }

        $student = $course->students()->whereKey($user->id)->first();

        if ($student !== null && $student->pivot->completed_at === null) {
            $course->students()->updateExistingPivot($user->id, ['completed_at' => now()]);
        }
    }
}
