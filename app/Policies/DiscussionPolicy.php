<?php

namespace App\Policies;

use App\Enums\DiscussionType;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\User;

class DiscussionPolicy
{
    /**
     * Any participant of the course may browse its discussions.
     */
    public function viewAny(User $user, Course $course): bool
    {
        return $course->hasParticipant($user);
    }

    /**
     * A user may view a discussion if they take part in its course.
     */
    public function view(User $user, Discussion $discussion): bool
    {
        return $this->course($discussion)->hasParticipant($user);
    }

    /**
     * Participants may start general discussions; only managers may start
     * announcements.
     */
    public function create(User $user, Course $course, DiscussionType $type = DiscussionType::General): bool
    {
        if ($type === DiscussionType::Announcement) {
            return $course->isManagedBy($user);
        }

        return $course->hasParticipant($user);
    }

    /**
     * Course managers may update any discussion; authors may update their own.
     */
    public function update(User $user, Discussion $discussion): bool
    {
        return $this->course($discussion)->isManagedBy($user)
            || $discussion->created_by_id === $user->id;
    }

    /**
     * Deleting follows the same rule as updating.
     */
    public function delete(User $user, Discussion $discussion): bool
    {
        return $this->update($user, $discussion);
    }

    /**
     * Only course managers may open or close a discussion.
     */
    public function setStatus(User $user, Discussion $discussion): bool
    {
        return $this->course($discussion)->isManagedBy($user);
    }

    /**
     * Resolve the course a discussion belongs to.
     */
    private function course(Discussion $discussion): Course
    {
        return $discussion->discussable;
    }
}
