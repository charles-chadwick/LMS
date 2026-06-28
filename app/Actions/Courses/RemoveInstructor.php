<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveInstructor
{
    /**
     * Remove an instructor from the course, keeping at least one assigned.
     *
     * @throws ValidationException when the user is the course's only instructor
     */
    public function execute(Course $course, User $user): void
    {
        $is_only_instructor = $course->instructors()->whereKey($user->id)->exists()
            && $course->instructors()->count() === 1;

        if ($is_only_instructor) {
            throw ValidationException::withMessages([
                'user' => 'A course must have at least one instructor.',
            ]);
        }

        $course->instructors()->detach($user);
    }
}
