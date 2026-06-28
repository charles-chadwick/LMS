<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableInstructors
{
    /**
     * List Instructor/Admin-role users who are not yet instructors of the course.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course): Collection
    {
        $assigned_ids = $course->instructors()->pluck('users.id');

        return User::whereHas('roles', fn ($query) => $query->whereIn('name', UserRole::values(UserRole::Admin, UserRole::Instructor)))
            ->whereNotIn('id', $assigned_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
