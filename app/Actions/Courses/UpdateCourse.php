<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Traits\SanitizesHtml;
use Illuminate\Http\UploadedFile;

class UpdateCourse
{
    use SanitizesHtml;

    /**
     * Update an existing course with validated attributes.
     *
     * A newly uploaded cover replaces any existing one. When no new cover is
     * provided and $removeCover is true, the existing cover is cleared.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Course $course, array $attributes, ?UploadedFile $cover = null, bool $removeCover = false): Course
    {
        if (array_key_exists('description', $attributes)) {
            $attributes['description'] = $this->sanitizeHtml($attributes['description']);
        }

        $course->update($attributes);

        if ($cover instanceof UploadedFile) {
            $course->addMedia($cover)->toMediaCollection('cover');
        } elseif ($removeCover) {
            $course->clearMediaCollection('cover');
        }

        return $course;
    }
}
