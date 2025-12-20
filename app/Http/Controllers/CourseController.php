<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Course::query()
            ->with(['instructors', 'students'])
            ->withCount(['pages', 'students', 'instructors']);

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sort_by = $request->get('sortBy', 'created_at');
        $sort_direction = $request->get('sortDirection', 'desc');
        $query->orderBy($sort_by, $sort_direction);

        // Paginate results
        $courses = $query->paginate($request->get('perPage', 15))
            ->withQueryString();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only(['search', 'status', 'sortBy', 'sortDirection']),
        ]);
    }

    /**
     * Show the form for creating a new course.
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Courses/Form');
    }

    /**
     * Store a newly created course in storage.
     *
     * @param StoreCourseRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->validated());

        activity()
            ->performedOn($course)
            ->causedBy(auth()->user())
            ->log('Course created');

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     *
     * @param Course $course
     * @return Response
     */
    public function show(Course $course): Response
    {
        $course->load([
            'pages',
            'instructors',
            'students',
            'createdBy',
            'updatedBy',
        ]);

        $course->loadCount(['pages', 'students', 'instructors']);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }

    /**
     * Show the form for editing the specified course.
     *
     * @param Course $course
     * @return Response
     */
    public function edit(Course $course): Response
    {
        return Inertia::render('Courses/Form', [
            'course' => $course,
        ]);
    }

    /**
     * Update the specified course in storage.
     *
     * @param UpdateCourseRequest $request
     * @param Course $course
     * @return RedirectResponse
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        activity()
            ->performedOn($course)
            ->causedBy(auth()->user())
            ->log('Course updated');

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course from storage.
     *
     * @param Course $course
     * @return RedirectResponse
     */
    public function destroy(Course $course): RedirectResponse
    {
        $course_title = $course->title;

        activity()
            ->performedOn($course)
            ->causedBy(auth()->user())
            ->log('Course deleted');

        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' deleted successfully.");
    }

    /**
     * Restore the specified course from soft deletion.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function restore(int $id): RedirectResponse
    {
        $course = Course::withTrashed()->findOrFail($id);
        $course->restore();

        activity()
            ->performedOn($course)
            ->causedBy(auth()->user())
            ->log('Course restored');

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course restored successfully.');
    }

    /**
     * Permanently delete the specified course.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function forceDestroy(int $id): RedirectResponse
    {
        $course = Course::withTrashed()->findOrFail($id);
        $course_title = $course->title;

        activity()
            ->performedOn($course)
            ->causedBy(auth()->user())
            ->log('Course permanently deleted');

        $course->forceDelete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Course '{$course_title}' permanently deleted.");
    }
}
