<?php

namespace App\Actions\Discussions;

use App\Enums\DiscussionPostStatus;
use App\Models\Course;
use App\Models\Discussion;
use App\Traits\SanitizesHtml;

class CreateDiscussion
{
    use SanitizesHtml;

    /**
     * Start a new discussion on a course, seeding it with the author's first post.
     *
     * @param  array{type: string, title: string, body: string}  $attributes
     */
    public function execute(Course $course, array $attributes): Discussion
    {
        $discussion = $course->discussions()->create([
            'type' => $attributes['type'],
            'title' => $attributes['title'],
        ]);

        $discussion->posts()->create([
            'status' => DiscussionPostStatus::Published,
            'content' => $this->sanitizeHtml($attributes['body']),
        ]);

        return $discussion;
    }
}
