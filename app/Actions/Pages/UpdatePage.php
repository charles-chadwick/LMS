<?php

namespace App\Actions\Pages;

use App\Models\Page;

class UpdatePage
{
    /**
     * Update an existing page from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        return $page;
    }
}
