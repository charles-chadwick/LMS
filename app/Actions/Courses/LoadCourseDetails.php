<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class LoadCourseDetails
{
    /**
     * Eager load the relationships and counts needed to display a course.
     *
     * Students only see published pages; managers (admins and assigned
     * instructors) see pages of every status.
     */
    public function execute(Course $course, User $user): Course
    {
        $seesAllPages = $course->isManagedBy($user);

        $course->load([
            'media',
            'pages' => function ($query) use ($seesAllPages) {
                $query->select('id', 'course_id', 'order', 'status', 'title');

                if (! $seesAllPages) {
                    $query->published();
                }
            },
            'instructors' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                    ->with('media')->orderBy('first_name')->orderBy('users.id')->limit(25);
            },
            'students' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                    ->with('media')->orderBy('first_name')->orderBy('users.id')->limit(25);
            },
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
        ]);

        $course->loadCount([
            'pages' => function ($query) use ($seesAllPages) {
                if (! $seesAllPages) {
                    $query->published();
                }
            },
            'students',
            'instructors',
        ]);

        return $course;
    }
}
