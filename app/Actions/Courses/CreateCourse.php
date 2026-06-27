<?php

namespace App\Actions\Courses;

use App\Models\Course;

class CreateCourse
{
    /**
     * Create a new course from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Course
    {
        return Course::create($attributes);
    }
}
