<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Traits\SanitizesHtml;

class CreateCourse
{
    use SanitizesHtml;

    /**
     * Create a new course from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Course
    {
        if (array_key_exists('description', $attributes)) {
            $attributes['description'] = $this->sanitizeHtml($attributes['description']);
        }

        return Course::create($attributes);
    }
}
