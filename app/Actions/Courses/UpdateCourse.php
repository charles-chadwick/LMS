<?php

namespace App\Actions\Courses;

use App\Models\Course;

class UpdateCourse
{
    /**
     * Update an existing course with validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Course $course, array $attributes): Course
    {
        $course->update($attributes);

        return $course;
    }
}
