<?php

namespace App\Traits;

/**
 * Trait for components that accept free-text search input from users.
 *
 * Search terms are interpolated into SQL `LIKE '%...%'` patterns, so any
 * stray whitespace the user pasted in becomes part of the pattern and
 * silently changes the result set. Normalize before it reaches the query.
 */
trait NormalizesSearchInput
{
    /**
     * Whitespace characters that survive a copy/paste but that PHP's trim()
     * does not strip by default. Non-breaking spaces in particular are common
     * when pasting out of Word, Excel, or a rendered web page.
     */
    protected static array $searchWhitespace = [
        "\u{00A0}", // no-break space
        "\u{2007}", // figure space
        "\u{202F}", // narrow no-break space
    ];

    /**
     * Zero-width characters are dropped outright rather than turned into
     * spaces, so a pasted "GJB<ZWSP>2" still matches the GJB2 symbol.
     */
    protected static array $searchInvisibles = [
        "\u{FEFF}", // zero width no-break space / BOM
        "\u{200B}", // zero width space
        "\u{200C}", // zero width non-joiner
        "\u{200D}", // zero width joiner
    ];

    /**
     * Normalize a user-supplied search term for use in a LIKE pattern.
     *
     * Converts exotic whitespace to plain spaces, trims the ends, and collapses
     * internal runs of whitespace to a single space.
     *
     * @param  string|null  $term
     * @return string
     */
    protected function normalizeSearchTerm($term): string
    {
        if (!is_string($term)) {
            return '';
        }

        $term = str_replace(static::$searchInvisibles, '', $term);
        $term = str_replace(static::$searchWhitespace, ' ', $term);

        return trim(preg_replace('/\s+/u', ' ', $term));
    }
}
