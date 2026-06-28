<?php

namespace App\Actions\Pages;

use App\Models\Page;
use App\Traits\SanitizesHtml;

class CreatePage
{
    use SanitizesHtml;

    /**
     * Create a new page, appending it after the course's existing pages.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Page
    {
        $next_order = (int) Page::where('course_id', $attributes['course_id'])->max('order') + 1;

        return Page::create([
            ...$attributes,
            'order' => $next_order,
            'content' => $this->sanitizeHtml($attributes['content']),
        ]);
    }
}
