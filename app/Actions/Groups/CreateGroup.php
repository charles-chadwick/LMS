<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateGroup
{
    use AsAction;

    /**
     * Create a new group.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Group
    {
        return Group::create($attributes);
    }
}
