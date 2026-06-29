<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
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
        $course->loadMissing(['students', 'instructors']);
        $excluded_ids = $course->students->pluck('id')->merge($course->instructors->pluck('id'));

        return User::whereHas('roles', fn ($query) => $query->where('name', UserRole::Student->value))
            ->whereNotIn('id', $excluded_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
