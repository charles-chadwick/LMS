<?php

namespace App\Actions\Courses;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchCourseInstructors
{
    /**
     * Paginate the course's instructor roster, optionally filtered by a
     * name/email search term.
     */
    public function execute(Course $course, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $course->instructors()
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->paginate($perPage, ['users.id', 'users.first_name', 'users.last_name', 'users.email']);
    }
}
