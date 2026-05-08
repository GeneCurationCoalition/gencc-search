<?php

return [
    'cache_ttl'   => (int) env('RELEASE_CACHE_TTL_SECONDS', 600),
    'daily_quota' => (int) env('RELEASE_DOWNLOAD_DAILY_QUOTA', 20),
];
