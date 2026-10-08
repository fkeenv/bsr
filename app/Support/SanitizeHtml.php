<?php

namespace App\Support;

use Illuminate\Support\Str;

class SanitizeHtml
{
    private const string AllowedTags = '<p><br><strong><em><s><ul><ol><li><h2><h3><blockquote><code><pre><hr>';

    public static function legalDocument(string $html): string
    {
        return self::richText($html);
    }

    public static function announcement(string $html): string
    {
        return self::richText($html);
    }

    public static function richText(string $html): string
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

    public static function plainText(string $html): string
    {
        $visible = preg_replace('/<(script|style|template)\b[^>]*>.*?<\/\1\s*>/is', '', $html) ?? '';
        $spaced = preg_replace('/<\/?(?:p|br|h[1-6]|li|ul|ol|blockquote|pre|hr)\b[^>]*>/i', ' ', $visible) ?? '';

        return Str::squish(html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
