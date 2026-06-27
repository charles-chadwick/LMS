<?php

namespace App\Actions\Pages;

use App\Models\Page;

class LoadPageDetails
{
    /**
     * Eager load the relationships needed to display a page.
     */
    public function execute(Page $page): Page
    {
        $page->load([
            'course:id,title,code',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
        ]);

        return $page;
    }
}
