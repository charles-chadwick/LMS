<?php

namespace App\Http\Controllers;

use App\Actions\Discussions\CreatePost;
use App\Actions\Discussions\DeletePost;
use App\Actions\Discussions\UpdatePost;
use App\Http\Requests\StoreDiscussionPostRequest;
use App\Http\Requests\UpdateDiscussionPostRequest;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use Illuminate\Http\RedirectResponse;

class DiscussionPostController extends Controller
{
    /**
     * Add a reply to a discussion.
     */
    public function store(StoreDiscussionPostRequest $request, Course $course, Discussion $discussion, CreatePost $createPost): RedirectResponse
    {
        $createPost->execute($discussion, $request->validated()['content']);

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Reply posted successfully.');
    }

    /**
     * Update a reply.
     */
    public function update(UpdateDiscussionPostRequest $request, Course $course, Discussion $discussion, DiscussionPost $post, UpdatePost $updatePost): RedirectResponse
    {
        $updatePost->execute($post, $request->validated()['content']);

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Reply updated successfully.');
    }

    /**
     * Delete a reply.
     */
    public function destroy(Course $course, Discussion $discussion, DiscussionPost $post, DeletePost $deletePost): RedirectResponse
    {
        $this->authorize('delete', $post);

        $deletePost->execute($post);

        return redirect()
            ->route('courses.discussions.show', [$course, $discussion])
            ->with('success', 'Reply deleted successfully.');
    }
}
