## Summary

Release 2.2.0 adds the gene–disease conflict viewer, improves gene-listing filters and search behavior, expands classification statistics, and updates public-facing About and FAQ content.

Classification behavior is now driven by stable CURIE metadata rather than database IDs, mutable names, or database ordering.

## Highlights

### Conflict viewer

- Adds `/conflict-viewer`, showing gene, disease, and mode-of-inheritance groups containing both D/S/M (Definitive, Strong, or Moderate) and L/P/R/N (Limited, Disputed, Refuted, or No Known Disease Relationship) submissions.
- Excludes Supportive and Animal Model Only submissions from conflict membership, pills, totals, and submitter filtering. Animal Model Only should be revisited in a product follow-up to confirm whether exclusion remains the intended behavior.
- Supports a shareable submitter exclusion filter.
- Orders classifications and submitters by canonical evidence strength.
- Preserves conflicts where contradictory submissions come from the same submitter.
- Uses CURIE metadata for conflict sides, ordering, and pill colors.
- Caches computed conflicts for six hours and provides `php artisan conflicts:clear-cache`.
- The route is available but is not yet linked from site navigation.

Related to #194. Implemented initially in #206.

### Gene listing filters and search

- Adds Select all/Select none actions for classification and submitter filters.
- Persists active filters in the URL so filtered listings can be bookmarked and shared.
- Adds an active-filter summary and clear-all action.
- Preserves compatibility with the legacy submitter query parameter.
- Normalizes pasted search input by trimming and collapsing Unicode whitespace and removing zero-width formatting characters.
- Covers gene-symbol and disease searches on both the main genes listing and member submission listings.

Closes #203, #204, and #207. Implemented in #209, #213, and #215.

### Statistics and classification metadata

- Adds a second statistics chart counting each gene once under its strongest classification.
- Keeps the existing submission-count chart.
- Makes chart and member-classification links open correctly filtered gene listings.
- Uses a CURIE-keyed vocabulary as the canonical source for classification order, filtering, styling, and conflict behavior.
- Keys member summary counts by stable classification CURIEs, eliminating repeated classification-ID queries.
- Caps dominant chart bars at their container width.

Closes #210. Implemented in #216 and #217, with release-review follow-ups.

### Content updates

- Adds funding and review-disclaimer language to the About page.
- Updates the FAQ with:

  - The GenCC marker paper
  - Revised clinical-validity terminology
  - The Supportive classification
  - A GenCC publications section

- Retains the existing validity-terms anchor for incoming links and bookmarks.

Closes #208 and #211. Implemented in #212 and #214.

### CI and regression coverage

- Runs the test workflow for release branches.
- Adds coverage for conflict computation and facets, filter URL persistence, select-all/none behavior, Unicode search normalization, classification chart links, member summaries, and strongest-classification statistics.
- Adds SQLite shims so additional listing behavior runs in CI instead of being skipped.

## Validation

```text
332 passed
9 skipped
0 failed
```

The skipped tests are existing MySQL-specific or unavailable-route tests.

## Deployment notes

- No database migration is required.
- Conflict results now use cache key `conflict-viewer.triples.v5`, so older cached result shapes are bypassed automatically.
- The conflict viewer is not linked from navigation in this release.
- No new environment variables or configuration changes are required.

## Included PRs

- #206 — Add gene-disease conflict viewer with faceted filtering
- #209 — Trim whitespace from search terms before building LIKE patterns
- #212 — Add funding and review disclaimer to About page
- #213 — Add select all/none to classification and submitter filters
- #214 — Apply the agreed FAQ text revisions
- #215 — Reflect genes listing filters in the URL
- #216 — Add a by-gene classifications chart to the statistics page
- #217 — Make the statistics chart links filter the genes listing
