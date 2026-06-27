<?php

namespace App\Actions\Pages;

use App\Models\Page;
use App\Traits\SanitizesHtml;

class UpdatePage
{
    use SanitizesHtml;

    /**
     * Update an existing page from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Page $page, array $attributes): Page
    {
        $page->update([
            ...$attributes,
            'content' => $this->sanitizeHtml($attributes['content']),
        ]);

        return $page;
    }
}
