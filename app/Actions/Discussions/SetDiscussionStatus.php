<?php

namespace App\Actions\Discussions;

use App\Enums\DiscussionStatus;
use App\Models\Discussion;

class SetDiscussionStatus
{
    /**
     * Open or close a discussion.
     */
    public function execute(Discussion $discussion, DiscussionStatus $status): Discussion
    {
        $discussion->update(['status' => $status]);

        return $discussion;
    }
}
