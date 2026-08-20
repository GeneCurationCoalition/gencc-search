<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait ShimsSqliteFunctions
{
    /**
     * The genes listing orders rows with REGEXP_SUBSTR, which natural-sorts gene
     * symbols so GJB2 precedes GJB10. MySQL has that function and SQLite does
     * not, which is why the older rendering tests skip themselves on SQLite —
     * but CI runs SQLite only (.github/workflows/tests.yaml), so skipping means
     * never running. Register a PHP equivalent so the query can execute; these
     * tests assert on which rows come back, not what order they come back in.
     *
     * Ports the MySQL signature REGEXP_SUBSTR(subject, pattern[, position[,
     * occurrence]]) — 1-indexed character position (hence mb_substr), NULL when
     * the Nth occurrence is absent. The 5th match_type argument is unused by
     * this query and not implemented.
     *
     * This alone does not reproduce production row order, since ORDER BY still
     * collates differently on the two engines. A test that asserts on ordering
     * needs further SQLite patching, or should be restricted to MySQL.
     */
    protected function shimRegexpSubstrForSqlite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction(
            'REGEXP_SUBSTR',
            function ($subject, $pattern, $position = 1, $occurrence = 1) {
                if ($subject === null || $pattern === null) {
                    return null;
                }

                $offset = max(0, ((int) $position) - 1);
                $haystack = mb_substr((string) $subject, $offset);

                if (!preg_match_all('/' . $pattern . '/u', $haystack, $matches)) {
                    return null;
                }

                return $matches[0][((int) $occurrence) - 1] ?? null;
            }
        );
    }
}
