<?php

namespace App\Actions\Discussions;

use App\Models\Discussion;

class LoadDiscussionDetails
{
    /**
     * Eager load the relationships needed to display a discussion thread.
     */
    public function execute(Discussion $discussion): Discussion
    {
        $discussion->load([
            'created_by:id,first_name,last_name',
            'posts.created_by' => function ($query) {
                $query->select('id', 'first_name', 'last_name')->with('media');
            },
        ]);

        return $discussion;
    }
}
