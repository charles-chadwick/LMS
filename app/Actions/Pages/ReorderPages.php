<?php

namespace App\Actions\Pages;

use App\Models\Course;
use App\Models\Page;

class ReorderPages
{
    /**
     * Rewrite each page's order to match the given sequence of page IDs.
     *
     * @param  array<int, int>  $ordered_page_ids
     */
    public function execute(Course $course, array $ordered_page_ids): void
    {
        foreach ($ordered_page_ids as $position => $page_id) {
            Page::where('course_id', $course->id)
                ->where('id', $page_id)
                ->update(['order' => $position + 1]);
        }
    }
}
