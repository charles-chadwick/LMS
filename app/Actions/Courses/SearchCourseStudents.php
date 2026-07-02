<?php

namespace App\Actions\Courses;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchCourseStudents
{
    /**
     * Paginate the course's student roster, optionally filtered by a
     * name/email search term.
     */
    public function execute(Course $course, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $course->students()
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->paginate($perPage, ['users.id', 'users.first_name', 'users.last_name', 'users.email']);
    }
}
