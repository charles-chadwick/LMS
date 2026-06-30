<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseUser;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollGroupMembers
{
    /**
     * Bulk-enroll a group's current members into the course as students.
     *
     * Members with an active enrollment (student or instructor) are skipped, as
     * are members who lack the Student role. A member whose enrollment was
     * previously soft-deleted is restored as a student.
     *
     * @return int The number of members actually enrolled (attached or restored).
     */
    public function execute(Course $course, Group $group): int
    {
        return DB::transaction(function () use ($course, $group): int {
            $enrolled_count = 0;

            foreach ($group->users as $member) {
                if ($this->enrollMember($course, $member)) {
                    $enrolled_count++;
                }
            }

            return $enrolled_count;
        });
    }

    /**
     * Enroll a single member, returning whether an enrollment was created or restored.
     */
    private function enrollMember(Course $course, User $member): bool
    {
        if (! $member->hasRole(UserRole::Student)) {
            return false;
        }

        $enrollment = CourseUser::withTrashed()
            ->where('course_id', $course->id)
            ->where('user_id', $member->id)
            ->first();

        if ($enrollment === null) {
            $course->students()->attach($member, ['is_instructor' => false]);

            return true;
        }

        if ($enrollment->trashed()) {
            $enrollment->restore();
            $enrollment->update(['is_instructor' => false]);

            return true;
        }

        return false;
    }
}
