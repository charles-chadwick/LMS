<?php

namespace App\Events;

use App\Models\DiscussionPost;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscussionPostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public DiscussionPost $post)
    {
        $this->post->loadMissing(['discussion', 'created_by']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('courses.'.$this->post->discussion->on.'.discussions'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $discussion = $this->post->discussion;
        $author = $this->post->created_by;

        return [
            'course_id' => (int) $discussion->on,
            'discussion_id' => $discussion->id,
            'discussion_title' => $discussion->title,
            'post_id' => $this->post->id,
            'author_id' => $this->post->created_by_id,
            'author_name' => $author ? "{$author->first_name} {$author->last_name}" : 'Someone',
        ];
    }
}
