<?php

namespace App\Traits;

/**
 * Trait for components that accept free-text search input from users.
 *
 * Search terms are interpolated into SQL `LIKE '%...%'` patterns, so any
 * stray whitespace the user pasted in becomes part of the pattern and
 * silently changes the result set. Normalize before it reaches the query.
 *
 * Note this is deliberately one-sided: the search term is normalized, the
 * column it is compared against is not. Stored values are assumed clean, so
 * collapsing an internal run of spaces on the query side only would fail to
 * match a stored value that really does contain a double space. Either clean
 * the data at ingest or add a matching REPLACE() on the column — do not reach
 * for this trait to paper over dirty stored values.
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
        // collapse the term to '' — and an empty term builds '%%', matching
        // every row. Bailing out here is what prevents that, and it is the only
        // guarantee this branch makes.
        //
        // What happens to the malformed bytes afterwards is MySQL's call, not
        // ours: they are bound into the LIKE pattern as-is. Verified on MySQL
        // 8.0.46 (utf8mb4/utf8mb4_unicode_ci, strict mode) that the bytes
        // round-trip unchanged and the comparison matches nothing, raising a
        // non-fatal "Warning 1300 Invalid utf8mb4 character string" — strict
        // mode only escalates warnings for data-change statements, so a SELECT
        // is unaffected. If a future server truncated the parameter at the
        // offending byte instead, a leading-invalid term would degrade to '%'
        // and match everything; strip the bad bytes here (iconv with
        // //IGNORE) rather than relying on comparison semantics if that ever
        // shows up.
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
