<?php

namespace App\Http\Controllers;

use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\RemoveInstructor;
use App\Http\Requests\StoreCourseInstructorRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CourseInstructorController extends Controller
{
    /**
     * Assign an instructor to the course.
     */
    public function store(StoreCourseInstructorRequest $request, Course $course, AssignInstructor $assignInstructor): RedirectResponse
    {
        $user = User::findOrFail($request->validated()['user_id']);

        $assignInstructor->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Instructor added successfully.');
    }

    /**
     * Remove an instructor from the course.
     */
    public function destroy(Course $course, User $user, RemoveInstructor $removeInstructor): RedirectResponse
    {
        $this->authorize('manageInstructors', $course);

        $removeInstructor->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Instructor removed successfully.');
    }
}
