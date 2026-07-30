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
     * Normalize a user-supplied search term for use in a LIKE pattern.
     *
     * Drops invisible formatting characters, collapses every kind of whitespace
     * — including the non-breaking and exotic spaces that survive a copy/paste
     * out of Word, Excel, or a rendered web page — and trims the ends.
     *
     * @param  string|null  $term
     * @return string
     */
    protected function normalizeSearchTerm($term): string
    {
        if (!is_string($term)) {
            return '';
        }

        // Invalid UTF-8 makes the /u patterns below return null, which would
        // collapse the term to '' — and an empty term matches every row. Fall
        // back to a plain trim so a malformed term matches nothing instead.
        if (preg_match('//u', $term) !== 1) {
            return trim($term);
        }

        // Cf covers the zero-width characters (ZWSP, ZWNJ, ZWJ, BOM) plus the
        // word joiner, soft hyphen, and bidi marks. Dropped outright rather
        // than turned into spaces, so a pasted "GJB<ZWSP>2" still matches GJB2.
        $term = preg_replace('/\p{Cf}/u', '', $term);

        // \s in UTF mode already folds no-break, figure, thin, and ideographic
        // spaces on PCRE2; \p{Z} states that intent explicitly rather than
        // leaning on how the PCRE library happens to be built.
        return trim(preg_replace('/[\s\p{Z}]+/u', ' ', $term));
    }
}
