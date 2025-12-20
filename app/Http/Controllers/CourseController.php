<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Traits\HasSearchFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    use HasSearchFilter;

    /**
     * Display a listing of the courses.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request) : Response
    {
        $query = Course::query()
            ->with([
                'instructors',
                'students'
            ])
            ->withCount([
                'pages',
                'students',
                'instructors'
            ]);

        // Apply common filters
        $query = $this->applyCommonFilters($query, $request, [
            'title',
            'code'
        ]);

        // Paginate results
        $courses = $query->paginate($request->get('perPage', 15))
            ->withQueryString();

        return Inertia::render('Courses/Index', [
            'courses'        => $courses,
            'filters'        => $request->only([
                'search',
                'status',
                'sortBy',
                'sortDirection'
            ]),
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new course.
     *
     * @return Response
     */
    public function create() : Response
    {
        return Inertia::render('Courses/Form', [
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Store a newly created course in storage.
     *
     * @param  StoreCourseRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreCourseRequest $request) : RedirectResponse
    {
        $course = Course::create($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     *
     * @param  Course  $course
     * @return Response
     */
    public function show(Course $course) : Response
    {
        $course->load([
            'pages',
            'instructors',
            'students',
            'created_by',
            'updated_by',
        ]);

        $course->loadCount([
            'pages',
            'students',
            'instructors'
        ]);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }

    /**
     * Show the form for editing the specified course.
     *
     * @param  Course  $course
     * @return Response
     */
    public function edit(Course $course) : Response
    {
        return Inertia::render('Courses/Form', [
            'course'         => $course,
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Update the specified course in storage.
     *
     * @param  UpdateCourseRequest  $request
     * @param  Course  $course
     * @return RedirectResponse
     */
    public function update(UpdateCourseRequest $request, Course $course) : RedirectResponse
    {
        $course->update($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course from storage.
     *
     * @param  Course  $course
     * @return RedirectResponse
     */
    public function destroy(Course $course) : RedirectResponse
    {
        $course_title = $course->title;

        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' deleted successfully.");
    }

    /**
     * Restore the specified course from soft deletion.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function restore(int $id) : RedirectResponse
    {
        $course = Course::withTrashed()
            ->findOrFail($id);
        $course->restore();

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course restored successfully.');
    }

    /**
     * Permanently delete the specified course.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function forceDestroy(int $id) : RedirectResponse
    {
        $course = Course::withTrashed()
            ->findOrFail($id);
        $course_title = $course->title;

        $course->forceDelete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' permanently deleted.");
    }
}
