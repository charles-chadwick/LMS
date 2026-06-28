<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class AssignStudent
{
    /**
     * Assign a user to the course as a student.
     */
    public function execute(Course $course, User $user): void
    {
        $course->students()->attach($user, ['is_instructor' => false]);
    }
}
