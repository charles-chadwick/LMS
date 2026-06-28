<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Traits\SanitizesHtml;

class UpdateCourse
{
    use SanitizesHtml;

    /**
     * Update an existing course with validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Course $course, array $attributes): Course
    {
        if (array_key_exists('description', $attributes)) {
            $attributes['description'] = $this->sanitizeHtml($attributes['description']);
        }

        $course->update($attributes);

        return $course;
    }
}
