<?php

namespace App\Actions\Discussions;

use App\Models\Discussion;

class DeleteDiscussion
{
    /**
     * Soft delete a discussion and its posts.
     */
    public function execute(Discussion $discussion): void
    {
        $discussion->posts()->delete();

        $discussion->delete();
    }
}
