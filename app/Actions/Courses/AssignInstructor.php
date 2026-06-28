<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class AssignInstructor
{
    /**
     * Assign a user to the course as an instructor.
     */
    public function execute(Course $course, User $user): void
    {
        $course->instructors()->attach($user, ['is_instructor' => true]);
    }
}
