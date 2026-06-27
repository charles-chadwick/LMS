<?php

namespace App\Actions\Pages;

use App\Models\Page;

class DeletePage
{
    /**
     * Soft delete a page and return its title for messaging.
     */
    public function execute(Page $page): string
    {
        $page_title = $page->title;

        $page->delete();

        return $page_title;
    }
}
