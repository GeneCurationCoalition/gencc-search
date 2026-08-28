<?php

return [
    'cache_ttl' => (int) env('RELEASE_CACHE_TTL_SECONDS', 600),
    'daily_quota' => (int) env(
        'DOWNLOAD_DAILY_QUOTA',
        env('RELEASE_DOWNLOAD_DAILY_QUOTA', 20)
    ),
    'conflict_export_cache_ttl' => (int) env('CONFLICT_EXPORT_CACHE_TTL_SECONDS', 21600),
];
