<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableInstructors
{
    /**
     * List Instructor/Admin-role users who are not yet instructors of the course,
     * optionally filtered by a name/email search term and capped for a typeahead.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course, ?string $search = null, int $limit = 20): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->whereIn('name', UserRole::values(UserRole::Admin, UserRole::Instructor)))
            ->whereDoesntHave('courses', fn ($query) => $query
                ->whereKey($course->getKey())
                ->where('courses_users.is_instructor', true))
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
