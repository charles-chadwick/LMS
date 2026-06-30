<?php

namespace App\Actions\Discussions;

use App\Models\DiscussionPost;
use App\Traits\SanitizesHtml;

class UpdatePost
{
    use SanitizesHtml;

    /**
     * Update a discussion post's content.
     */
    public function execute(DiscussionPost $post, string $content): DiscussionPost
    {
        $post->update(['content' => $this->sanitizeHtml($content)]);

        return $post;
    }
}
