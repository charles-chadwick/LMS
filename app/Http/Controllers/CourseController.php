<?php

namespace App\Http\Controllers;

use App\Actions\Courses\CreateCourse;
use App\Actions\Courses\DeleteCourse;
use App\Actions\Courses\ForceDeleteCourse;
use App\Actions\Courses\ListCourses;
use App\Actions\Courses\LoadCourseDetails;
use App\Actions\Courses\RestoreCourse;
use App\Actions\Courses\UpdateCourse;
use App\Enums\CourseStatus;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     */
    public function index(Request $request, ListCourses $listCourses): Response
    {
        return Inertia::render('Courses/Index', [
            'courses' => $listCourses->execute($request),
            'filters' => $request->only([
                'search',
                'status',
                'sortBy',
                'sortDirection',
            ]),
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): Response
    {
        return Inertia::render('Courses/Form', [
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request, CreateCourse $createCourse): RedirectResponse
    {
        $course = $createCourse->execute($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course, LoadCourseDetails $loadCourseDetails): Response
    {
        return Inertia::render('Courses/Show', [
            'course' => $loadCourseDetails->execute($course),
        ]);
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course): Response
    {
        return Inertia::render('Courses/Form', [
            'course' => $course,
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course, UpdateCourse $updateCourse): RedirectResponse
    {
        $updateCourse->execute($course, $request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course, DeleteCourse $deleteCourse): RedirectResponse
    {
        $course_title = $deleteCourse->execute($course);

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' deleted successfully.");
    }

    /**
     * Restore the specified course from soft deletion.
     */
    public function restore(int $id, RestoreCourse $restoreCourse): RedirectResponse
    {
        $course = $restoreCourse->execute($id);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course restored successfully.');
    }

    /**
     * Permanently delete the specified course.
     */
    public function forceDestroy(int $id, ForceDeleteCourse $forceDeleteCourse): RedirectResponse
    {
        $course_title = $forceDeleteCourse->execute($id);

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' permanently deleted.");
    }
}
