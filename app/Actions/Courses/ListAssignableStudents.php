<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableStudents
{
    /**
     * List Student-role users who are neither enrolled in nor instructing the course.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course): Collection
    {
        $student_ids = $course->students()->pluck('users.id');
        $instructor_ids = $course->instructors()->pluck('users.id');
        $excluded_ids = $student_ids->merge($instructor_ids);

        return User::whereHas('roles', fn ($query) => $query->where('name', 'Student'))
            ->whereNotIn('id', $excluded_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
