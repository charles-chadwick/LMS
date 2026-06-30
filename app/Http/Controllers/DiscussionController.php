<?php

namespace App\Http\Controllers;

use App\Actions\Discussions\CreateDiscussion;
use App\Actions\Discussions\DeleteDiscussion;
use App\Actions\Discussions\LoadCourseDiscussions;
use App\Actions\Discussions\LoadDiscussionDetails;
use App\Actions\Discussions\SetDiscussionStatus;
use App\Actions\Discussions\UpdateDiscussion;
use App\Enums\DiscussionStatus;
use App\Enums\DiscussionType;
use App\Http\Requests\StoreDiscussionRequest;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class DiscussionController extends Controller
{
    /**
     * List the discussions on a course.
     */
    public function index(Request $request, Course $course, LoadCourseDiscussions $loadCourseDiscussions): Response
    {
        $this->authorize('viewAny', [Discussion::class, $course]);

        return Inertia::render('Discussions/Index', [
            'course' => $course->only('id', 'title', 'code'),
            'discussions' => $loadCourseDiscussions->execute($course),
            'types' => DiscussionType::options(),
            'can' => [
                'create' => $request->user()->can('create', [Discussion::class, $course]),
                'create_announcement' => $request->user()->can('create', [Discussion::class, $course, DiscussionType::Announcement]),
            ],
        ]);
    }

    /**
     * Show the form for starting a new discussion.
     */
    public function create(Request $request, Course $course): Response
    {
        $this->authorize('create', [Discussion::class, $course]);

        return Inertia::render('Discussions/Form', [
            'course' => $course->only('id', 'title', 'code'),
            'types' => DiscussionType::options(),
            'can' => [
                'create_announcement' => $request->user()->can('create', [Discussion::class, $course, DiscussionType::Announcement]),
            ],
        ]);
    }

    /**
     * Store a new discussion.
     */
    public function store(StoreDiscussionRequest $request, Course $course, CreateDiscussion $createDiscussion): RedirectResponse
    {
        $discussion = $createDiscussion->execute($course, $request->validated());

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Discussion started successfully.');
    }

    /**
     * Show a single discussion thread.
     */
    public function show(Request $request, Course $course, Discussion $discussion, LoadDiscussionDetails $loadDiscussionDetails): Response
    {
        $this->authorize('view', $discussion);

        return Inertia::render('Discussions/Show', [
            'course' => $course->only('id', 'title', 'code'),
            'discussion' => $loadDiscussionDetails->execute($discussion),
            'can' => [
                'update' => $request->user()->can('update', $discussion),
                'delete' => $request->user()->can('delete', $discussion),
                'set_status' => $request->user()->can('setStatus', $discussion),
                'reply' => $request->user()->can('create', [DiscussionPost::class, $discussion]),
                'moderate' => $course->isManagedBy($request->user()),
            ],
        ]);
    }

    /**
     * Show the form for editing a discussion.
     */
    public function edit(Course $course, Discussion $discussion): Response
    {
        $this->authorize('update', $discussion);

        return Inertia::render('Discussions/Form', [
            'course' => $course->only('id', 'title', 'code'),
            'discussion' => $discussion->only('id', 'type', 'title'),
            'types' => DiscussionType::options(),
            'can' => [
                'create_announcement' => $course->isManagedBy(request()->user()),
            ],
        ]);
    }

    /**
     * Update a discussion.
     */
    public function update(UpdateDiscussionRequest $request, Course $course, Discussion $discussion, UpdateDiscussion $updateDiscussion): RedirectResponse
    {
        $updateDiscussion->execute($discussion, $request->validated());

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Discussion updated successfully.');
    }

    /**
     * Open or close a discussion.
     */
    public function setStatus(Request $request, Course $course, Discussion $discussion, SetDiscussionStatus $setDiscussionStatus): RedirectResponse
    {
        $this->authorize('setStatus', $discussion);

        $validated = $request->validate([
            'status' => ['required', new Enum(DiscussionStatus::class)],
        ]);

        $setDiscussionStatus->execute($discussion, DiscussionStatus::from($validated['status']));

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Discussion status updated successfully.');
    }

    /**
     * Delete a discussion.
     */
    public function destroy(Course $course, Discussion $discussion, DeleteDiscussion $deleteDiscussion): RedirectResponse
    {
        $this->authorize('delete', $discussion);

        $deleteDiscussion->execute($discussion);

        return redirect()
            ->route('courses.discussions.index', $course)
            ->with('success', 'Discussion deleted successfully.');
    }
}
