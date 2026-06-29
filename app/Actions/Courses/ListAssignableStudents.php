<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableStudents
{
    /**
     * List Student-role users who are neither enrolled in nor instructing the course,
     * optionally filtered by a name/email search term and capped for a typeahead.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course, ?string $search = null, int $limit = 20): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', UserRole::Student->value))
            ->whereDoesntHave('courses', fn ($query) => $query->whereKey($course->getKey()))
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
