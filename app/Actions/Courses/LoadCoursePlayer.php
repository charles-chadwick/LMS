<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class LoadCoursePlayer
{
    /**
     * Build the take-course player payload for a student.
     *
     * @return array<string, mixed>
     */
    public function execute(Course $course, User $user, ?Page $currentPage = null): array
    {
        $pages = $course->pages()->published()->get(['id', 'course_id', 'order', 'title']);

        $completed_page_ids = $this->completedPageIds($course, $user);

        $items = [];
        $is_locked = false;
        foreach ($pages as $page) {
            $is_completed = $completed_page_ids->contains($page->id);
            $items[] = [
                'id' => $page->id,
                'title' => $page->title,
                'order' => $page->order,
                'is_completed' => $is_completed,
                'is_locked' => $is_locked,
            ];

            if (! $is_completed) {
                $is_locked = true;
            }
        }

        $total_count = $pages->count();
        $completed_count = $pages->filter(fn (Page $page) => $completed_page_ids->contains($page->id))->count();
        $is_complete = $total_count > 0 && $completed_count === $total_count;

        $current = $this->resolveCurrentPage($pages, collect($items), $currentPage);

        $student = $course->students()->whereKey($user->id)->first();

        return [
            'course' => $course->only('id', 'title', 'code', 'cover'),
            'pages' => $items,
            'current_page' => $current?->only('id', 'title', 'content'),
            'progress' => [
                'completed_count' => $completed_count,
                'total_count' => $total_count,
                'percent' => $total_count > 0 ? (int) round($completed_count / $total_count * 100) : 0,
            ],
            'is_complete' => $is_complete,
            'completed_at' => $student?->pivot->completed_at,
        ];
    }

    /**
     * The set of page ids the user has completed in this course.
     */
    private function completedPageIds(Course $course, User $user): Collection
    {
        return UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');
    }

    /**
     * Resolve which page to display: the requested page if unlocked,
     * otherwise the first incomplete page, otherwise the last page.
     *
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function resolveCurrentPage(Collection $pages, Collection $items, ?Page $requested): ?Page
    {
        if ($requested !== null) {
            $requested_item = $items->firstWhere('id', $requested->id);

            if ($requested_item !== null && ! $requested_item['is_locked']) {
                return Page::find($requested->id);
            }
        }

        $first_incomplete = $items->firstWhere('is_completed', false);
        $target_id = $first_incomplete['id'] ?? $pages->last()?->id;

        return $target_id !== null ? Page::find($target_id) : null;
    }
}
