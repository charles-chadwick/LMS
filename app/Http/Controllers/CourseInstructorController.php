<?php

namespace App\Http\Controllers;

use App\Actions\Courses\AssignInstructors;
use App\Actions\Courses\ListAssignableInstructors;
use App\Actions\Courses\RemoveInstructor;
use App\Actions\Courses\SearchCourseInstructors;
use App\Http\Requests\IndexCourseRosterRequest;
use App\Http\Requests\StoreCourseInstructorRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseInstructorController extends Controller
{
    /**
     * Search instructors who can still be assigned to the course (typeahead).
     */
    public function assignable(Request $request, Course $course, ListAssignableInstructors $listAssignableInstructors): JsonResponse
    {
        $this->authorize('manageInstructors', $course);

        return response()->json(
            $listAssignableInstructors->execute($course, $request->string('search')->toString() ?: null)
        );
    }

    /**
     * Paginate and search the course's enrolled instructor roster.
     */
    public function index(IndexCourseRosterRequest $request, Course $course, SearchCourseInstructors $searchCourseInstructors): JsonResponse
    {
        return response()->json(
            $searchCourseInstructors->execute($course, $request->string('search')->toString() ?: null)
        );
    }

    /**
     * Assign one or more instructors to the course.
     */
    public function store(StoreCourseInstructorRequest $request, Course $course, AssignInstructors $assignInstructors): RedirectResponse
    {
        $users = User::findMany($request->validated()['user_ids']);

        $count = $assignInstructors->execute($course, $users);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "{$count} instructor(s) added successfully.");
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
