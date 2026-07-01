<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\CourseUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignInstructors
{
    /**
     * Bulk-assign users to the course as instructors.
     *
     * Users with an active enrollment are skipped; a soft-deleted enrollment is
     * restored and flipped to instructor. Runs atomically.
     *
     * @param  Collection<int, User>  $users
     * @return int The number of instructors attached or restored.
     */
    public function execute(Course $course, Collection $users): int
    {
        return DB::transaction(function () use ($course, $users): int {
            $assigned_count = 0;

            foreach ($users as $user) {
                if ($this->assign($course, $user)) {
                    $assigned_count++;
                }
            }

            return $assigned_count;
        });
    }

    private function assign(Course $course, User $user): bool
    {
        $enrollment = CourseUser::withTrashed()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($enrollment === null) {
            $course->instructors()->attach($user, ['is_instructor' => true]);

            return true;
        }

        if ($enrollment->trashed()) {
            $enrollment->restore();
            $enrollment->update(['is_instructor' => true]);

            return true;
        }

        return false;
    }
}
