<?php

namespace App\Support;

class SanitizeHtml
{
    private const string AllowedTags = '<p><br><strong><em><s><ul><ol><li><h2><h3><blockquote><code><pre><hr>';

    public static function legalDocument(string $html): string
    {
        $stripped = strip_tags($html, self::AllowedTags);

        // strip_tags keeps attributes on allowed tags; drop them for a safe allow-list.
        $withoutAttributes = preg_replace('/<(\/?)([a-z0-9]+)[^>]*>/i', '<$1$2>', $stripped);

        return trim($withoutAttributes ?? '');
    }

    public static function isBlank(string $html): bool
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)) === '';
    }
}
