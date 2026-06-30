<?php

namespace App\Policies;

use App\Enums\DiscussionType;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\User;

class DiscussionPostPolicy
{
    /**
     * Determine whether the user may post in the discussion. The discussion
     * must be open, the user must take part in the course, and announcements
     * accept replies only from course managers.
     */
    public function create(User $user, Discussion $discussion): bool
    {
        if (! $discussion->isOpen()) {
            return false;
        }

        $course = $this->course($discussion);

        if ($discussion->type === DiscussionType::Announcement) {
            return $course->isManagedBy($user);
        }

        return $course->hasParticipant($user);
    }

    /**
     * Course managers may update any post; authors may update their own.
     */
    public function update(User $user, DiscussionPost $post): bool
    {
        return $this->course($post->discussion)->isManagedBy($user)
            || $post->created_by_id === $user->id;
    }

    /**
     * Deleting follows the same rule as updating.
     */
    public function delete(User $user, DiscussionPost $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Resolve the course a discussion belongs to.
     */
    private function course(Discussion $discussion): Course
    {
        return $discussion->discussable;
    }
}
