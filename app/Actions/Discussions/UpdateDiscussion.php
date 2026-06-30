<?php

namespace App\Actions\Discussions;

use App\Models\Discussion;

class UpdateDiscussion
{
    /**
     * Update a discussion's editable attributes.
     *
     * @param  array{type?: string, title?: string}  $attributes
     */
    public function execute(Discussion $discussion, array $attributes): Discussion
    {
        $discussion->update($attributes);

        return $discussion;
    }
}
