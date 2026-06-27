<?php

namespace App\Actions\Courses;

use App\Models\Course;

class ForceDeleteCourse
{
    /**
     * Permanently delete a course and return its title for messaging.
     */
    public function execute(int $course_id): string
    {
        $course = Course::withTrashed()->findOrFail($course_id);

        $course_title = $course->title;

        $course->forceDelete();

        return $course_title;
    }
}
