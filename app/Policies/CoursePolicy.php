<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Any authenticated user may browse courses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a course.
     */
    public function view(User $user, Course $course): bool
    {
        return true;
    }

    /**
     * Admins and instructors may create courses.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::Instructor]);
    }

    /**
     * Admins may update any course; instructors only the ones they teach.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->hasRole(UserRole::Admin) || $this->teaches($user, $course);
    }

    /**
     * Managing instructors follows the same rule as updating.
     */
    public function manageInstructors(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /**
     * Managing students follows the same rule as updating.
     */
    public function manageStudents(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /**
     * Deleting follows the same rule as updating.
     */
    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /**
     * Only admins may restore courses.
     */
    public function restore(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may permanently delete courses.
     */
    public function forceDelete(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user may take (work through) the course.
     */
    public function take(User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published
            && $course->students()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user may view their completion certificate.
     */
    public function viewCertificate(User $user, Course $course): bool
    {
        $student = $course->students()->whereKey($user->id)->first();

        return $student !== null && $student->pivot->completed_at !== null;
    }

    /**
     * Determine whether the user is an assigned instructor of the course.
     */
    private function teaches(User $user, Course $course): bool
    {
        return $user->hasRole(UserRole::Instructor)
            && $course->instructors()->whereKey($user->id)->exists();
    }
}
