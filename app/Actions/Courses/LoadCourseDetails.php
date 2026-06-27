<?php

namespace App\Actions\Courses;

use App\Models\Course;

class LoadCourseDetails
{
    /**
     * Eager load the relationships and counts needed to display a course.
     */
    public function execute(Course $course): Course
    {
        $course->load([
            'pages' => function ($query) {
                $query->select('id', 'course_id', 'order', 'status', 'title');
            },
            'instructors:id,first_name,last_name,email',
            'students:id,first_name,last_name,email',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
        ]);

        $course->loadCount([
            'pages',
            'students',
            'instructors',
        ]);

        return $course;
    }
}
