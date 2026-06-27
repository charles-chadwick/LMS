<?php

namespace App\Actions\Courses;

use App\Models\Course;

class DeleteCourse
{
    /**
     * Soft delete a course and return its title for messaging.
     */
    public function execute(Course $course): string
    {
        $course_title = $course->title;

        $course->delete();

        return $course_title;
    }
}
