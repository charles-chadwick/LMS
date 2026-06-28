<?php

namespace App\Http\Controllers;

use App\Actions\Courses\CompletePage;
use App\Actions\Courses\LoadCoursePlayer;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseLearnController extends Controller
{
    /**
     * Show the course player at the first incomplete page.
     */
    public function show(Request $request, Course $course, LoadCoursePlayer $loadCoursePlayer): Response
    {
        $this->authorize('take', $course);

        return Inertia::render('Courses/Learn', $loadCoursePlayer->execute($course, $request->user()));
    }

    /**
     * Show the course player at a specific (unlocked) page.
     */
    public function showPage(Request $request, Course $course, Page $page, LoadCoursePlayer $loadCoursePlayer): Response
    {
        $this->authorize('take', $course);

        return Inertia::render('Courses/Learn', $loadCoursePlayer->execute($course, $request->user(), $page));
    }

    /**
     * Mark the page complete and advance to the next incomplete page.
     */
    public function complete(Request $request, Course $course, Page $page, CompletePage $completePage): RedirectResponse
    {
        $this->authorize('take', $course);

        $next_page_id = $completePage->execute($request->user(), $course, $page);

        if ($next_page_id === null) {
            return redirect()
                ->route('courses.learn', $course)
                ->with('success', 'Course complete!');
        }

        return redirect()->route('courses.learn.page', [$course, $next_page_id]);
    }
}
