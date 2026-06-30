<?php

namespace App\Actions\Discussions;

use App\Models\DiscussionPost;

class DeletePost
{
    /**
     * Soft delete a discussion post.
     */
    public function execute(DiscussionPost $post): void
    {
        $post->delete();
    }
}
