<?php

namespace App\Actions\Courses;

use App\Models\Course;

class RestoreCourse
{
    /**
     * Restore a soft-deleted course by its identifier.
     */
    public function execute(int $course_id): Course
    {
        $course = Course::withTrashed()->findOrFail($course_id);

        $course->restore();

        return $course;
    }
}
