<?php

namespace App\Actions\Discussions;

use App\Enums\DiscussionPostStatus;
use App\Events\DiscussionPostCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Traits\SanitizesHtml;

class CreatePost
{
    use SanitizesHtml;

    /**
     * Add a published reply to a discussion and broadcast it to participants.
     */
    public function execute(Discussion $discussion, string $content): DiscussionPost
    {
        $post = $discussion->posts()->create([
            'status' => DiscussionPostStatus::Published,
            'content' => $this->sanitizeHtml($content),
        ]);

        $post->setRelation('discussion', $discussion);

        DiscussionPostCreated::dispatch($post);

        return $post;
    }
}
