<?php

namespace App\Http\Controllers;

use App\Actions\Courses\AssignStudents;
use App\Actions\Courses\EnrollGroups;
use App\Actions\Courses\ListAssignableGroups;
use App\Actions\Courses\ListAssignableStudents;
use App\Actions\Courses\RemoveStudent;
use App\Http\Requests\StoreCourseGroupStudentsRequest;
use App\Http\Requests\StoreCourseStudentRequest;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseStudentController extends Controller
{
    /**
     * Search students who can still be enrolled in the course (typeahead).
     */
    public function assignable(Request $request, Course $course, ListAssignableStudents $listAssignableStudents): JsonResponse
    {
        $this->authorize('manageStudents', $course);

        return response()->json(
            $listAssignableStudents->execute($course, $request->string('search')->toString() ?: null)
        );
    }

    /**
     * Enroll one or more students in the course.
     */
    public function store(StoreCourseStudentRequest $request, Course $course, AssignStudents $assignStudents): RedirectResponse
    {
        $users = User::findMany($request->validated()['user_ids']);

        $count = $assignStudents->execute($course, $users);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "{$count} student(s) added successfully.");
    }

    /**
     * Search groups whose members can be bulk-enrolled in the course (typeahead).
     */
    public function assignableGroups(Request $request, Course $course, ListAssignableGroups $listAssignableGroups): JsonResponse
    {
        $this->authorize('manageStudents', $course);

        return response()->json(
            $listAssignableGroups->execute($request->string('search')->toString() ?: null)
        );
    }

    /**
     * Bulk-enroll a group's current members in the course as students.
     */
    public function storeGroup(StoreCourseGroupStudentsRequest $request, Course $course, EnrollGroups $enrollGroups): RedirectResponse
    {
        $groups = Group::findMany($request->validated()['group_ids']);

        $enrolled_count = $enrollGroups->execute($course, $groups);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "{$enrolled_count} member(s) enrolled from the selected group(s).");
    }

    /**
     * Remove a student from the course.
     */
    public function destroy(Course $course, User $user, RemoveStudent $removeStudent): RedirectResponse
    {
        $this->authorize('manageStudents', $course);

        $removeStudent->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Student removed successfully.');
    }
}
