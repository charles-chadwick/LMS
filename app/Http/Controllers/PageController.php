<?php

namespace App\Http\Controllers;

use App\Actions\Pages\CreatePage;
use App\Actions\Pages\DeletePage;
use App\Actions\Pages\LoadPageDetails;
use App\Actions\Pages\ReorderPages;
use App\Actions\Pages\UpdatePage;
use App\Enums\CourseStatus;
use App\Http\Requests\ReorderPagesRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Show the form for creating a new page.
     */
    public function create(): Response
    {
        $this->authorize('create', Page::class);

        return Inertia::render('Pages/Form', [
            'courses' => Course::orderBy('title')->get(['id', 'title', 'code']),
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(StorePageRequest $request, CreatePage $createPage): RedirectResponse
    {
        $validated = $request->validated();

        // Adding a page requires permission to manage its target course.
        $this->authorize('update', Course::findOrFail($validated['course_id']));

        $page = $createPage->execute($validated);

        return redirect()
            ->route('pages.show', $page)
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified page.
     */
    public function show(Page $page, LoadPageDetails $loadPageDetails): Response
    {
        return Inertia::render('Pages/Show', [
            'page' => $loadPageDetails->execute($page),
        ]);
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Page $page): Response
    {
        $this->authorize('update', $page);

        return Inertia::render('Pages/Form', [
            'page' => $page,
            'courses' => Course::orderBy('title')->get(['id', 'title', 'code']),
            'status_options' => CourseStatus::options(),
        ]);
    }

    /**
     * Update the specified page in storage.
     */
    public function update(UpdatePageRequest $request, Page $page, UpdatePage $updatePage): RedirectResponse
    {
        $this->authorize('update', $page);

        $updatePage->execute($page, $request->validated());

        return redirect()
            ->route('pages.show', $page)
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Page $page, DeletePage $deletePage): RedirectResponse
    {
        $this->authorize('delete', $page);

        $course_id = $page->course_id;
        $page_title = $deletePage->execute($page);

        return redirect()
            ->route('courses.show', $course_id)
            ->with('success', "Page '{$page_title}' deleted successfully.");
    }

    /**
     * Persist a new ordering for the pages of a course.
     */
    public function reorder(ReorderPagesRequest $request, Course $course, ReorderPages $reorderPages): RedirectResponse
    {
        $this->authorize('update', $course);

        $reorderPages->execute($course, $request->validated()['pages']);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Pages reordered successfully.');
    }
}
