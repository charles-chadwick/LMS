<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Traits\SanitizesHtml;
use Illuminate\Http\UploadedFile;

class CreateCourse
{
    use SanitizesHtml;

    /**
     * Create a new course and assign its creator as an instructor.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $creator, ?UploadedFile $cover = null): Course
    {
        if (array_key_exists('description', $attributes)) {
            $attributes['description'] = $this->sanitizeHtml($attributes['description']);
        }

        $course = Course::create($attributes);

        $course->instructors()->attach($creator, ['is_instructor' => true]);

        if ($cover instanceof UploadedFile) {
            $course->addMedia($cover)->toMediaCollection('cover');
        }

        return $course;
    }
}
