<?php

namespace App\Policies;

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
        return $user->hasAnyRole(['Admin', 'Instructor']);
    }

    /**
     * Admins may update any course; instructors only the ones they teach.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->hasRole('Admin') || $this->teaches($user, $course);
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
        return $user->hasRole('Admin');
    }

    /**
     * Only admins may permanently delete courses.
     */
    public function forceDelete(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user is an assigned instructor of the course.
     */
    private function teaches(User $user, Course $course): bool
    {
        return $user->hasRole('Instructor')
            && $course->instructors()->whereKey($user->id)->exists();
    }
}
