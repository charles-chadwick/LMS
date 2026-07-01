<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    /**
     * Any authenticated user may view pages.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A page is viewable when it is published, or when the user manages its
     * course (an admin, or an assigned instructor).
     */
    public function view(User $user, Page $page): bool
    {
        return $page->status === CourseStatus::Published
            || $user->hasRole(UserRole::Admin)
            || $this->teaches($user, $page);
    }

    /**
     * Admins and instructors may reach the create form. The target course is
     * authorized separately (via CoursePolicy@update) when the page is stored.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::Instructor]);
    }

    /**
     * Admins may update any page; instructors only pages in courses they teach.
     */
    public function update(User $user, Page $page): bool
    {
        return $user->hasRole(UserRole::Admin) || $this->teaches($user, $page);
    }

    /**
     * Deleting follows the same rule as updating.
     */
    public function delete(User $user, Page $page): bool
    {
        return $this->update($user, $page);
    }

    /**
     * Determine whether the user is an assigned instructor of the page's course.
     */
    private function teaches(User $user, Page $page): bool
    {
        return $user->hasRole(UserRole::Instructor)
            && $page->course->instructors()->whereKey($user->id)->exists();
    }
}
