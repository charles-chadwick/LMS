<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class RemoveStudent
{
    /**
     * Remove a student from the course. A course may have zero students.
     */
    public function execute(Course $course, User $user): void
    {
        $course->students()->detach($user);
    }
}
