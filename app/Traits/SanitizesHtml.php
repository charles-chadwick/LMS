<?php

namespace App\Traits;

trait SanitizesHtml
{
    /**
     * Sanitize user-supplied HTML, stripping scripts and dangerous markup.
     * Returns null untouched so nullable fields stay nullable.
     */
    protected function sanitizeHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return clean($html);
    }
}
