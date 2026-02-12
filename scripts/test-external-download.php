#!/usr/bin/env php
<?php
/**
 * Test script to verify external users can download submissions
 *
 * Tests both normal and empty User-Agent headers to ensure the download
 * endpoint is accessible to all users including automated tools.
 * Tests both legacy format (default) and new format (?format=new).
 *
 * Usage:
 *   php scripts/test-external-download.php [base_url]
 *
 * Examples:
 *   php scripts/test-external-download.php
 *   php scripts/test-external-download.php https://search.thegencc.org
 *   php scripts/test-external-download.php http://localhost:8000
 */

$baseUrl = $argv[1] ?? 'https://search.thegencc.org';

echo "Testing external download access\n";
echo "================================\n\n";

$results = [];
$totalPassed = 0;
$totalTests = 0;

// Test cases: different User-Agent scenarios
$testCases = [
    'Normal User-Agent' => 'GenCC-Test/1.0 (External Download Test)',
    'Empty User-Agent' => '',
    'Hyphen User-Agent' => '-',
    'Python requests' => 'python-requests/2.28.0',
    'curl' => 'curl/7.88.0',
];

// Endpoints to test: legacy format is default (no parameter), new format requires ?format=new
$endpoints = [
    'legacy' => [
        'path' => '/download/action/submissions-export-csv',
        'expected_header' => 'uuid',
    ],
    'new' => [
        'path' => '/download/action/submissions-export-csv?format=new',
        'expected_header' => 'sgc_id',
    ],
];

foreach ($endpoints as $formatName => $endpoint) {
    $url = rtrim($baseUrl, '/') . $endpoint['path'];
    $expectedHeader = $endpoint['expected_header'];

    echo "Testing {$formatName} format\n";
    echo "URL: {$url}\n";
    echo "Expected first column: {$expectedHeader}\n";
    echo "----------------------------------------\n\n";

    foreach ($testCases as $name => $userAgent) {
        echo "Test: {$name}\n";
        echo "  User-Agent: " . ($userAgent === '' ? '(empty)' : "'{$userAgent}'") . "\n";

        $result = testDownload($url, $userAgent, $expectedHeader);
        $results["{$formatName}:{$name}"] = $result;
        $totalTests++;

        if ($result['success']) {
            echo "  Status: PASS\n";
            echo "  HTTP Code: {$result['http_code']}\n";
            echo "  Content-Type: {$result['content_type']}\n";
            echo "  Content-Length: " . formatBytes($result['content_length']) . "\n";
            echo "  First line: " . truncate($result['first_line'], 80) . "\n";
            $totalPassed++;
        } else {
            echo "  Status: FAIL\n";
            echo "  HTTP Code: {$result['http_code']}\n";
            echo "  Error: {$result['error']}\n";
        }
        echo "\n";
    }

    echo "\n";
}

// Summary
echo "Summary\n";
echo "-------\n";
echo "Passed: {$totalPassed}/{$totalTests}\n\n";

// Exit with error code if any test failed
exit($totalPassed === $totalTests ? 0 : 1);

/**
 * Test download with specified User-Agent
 */
function testDownload(string $url, string $userAgent, string $expectedHeader): array
{
    $result = [
        'success' => false,
        'http_code' => 0,
        'content_type' => '',
        'content_length' => 0,
        'first_line' => '',
        'error' => '',
    ];

    // Build headers - include User-Agent even if empty
    $headers = [];
    if ($userAgent !== '') {
        $headers[] = "User-Agent: {$userAgent}";
    }
    // For truly empty UA, we need to explicitly set it
    // PHP's stream wrapper doesn't send UA if not specified

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 30,
            'ignore_errors' => true, // Get response even on 4xx/5xx
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];

    $context = stream_context_create($opts);

    // Suppress warnings, we'll check the result
    $content = @file_get_contents($url, false, $context);

    if ($content === false) {
        $result['error'] = 'Failed to connect or retrieve content';
        return $result;
    }

    // Parse response headers
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $result['http_code'] = (int)$matches[1];
            }
            if (preg_match('/^Content-Type:\s*(.+)$/i', $header, $matches)) {
                $result['content_type'] = trim($matches[1]);
            }
            if (preg_match('/^Content-Length:\s*(\d+)$/i', $header, $matches)) {
                $result['content_length'] = (int)$matches[1];
            }
        }
    }

    // If we didn't get content-length from headers, use actual length
    if ($result['content_length'] === 0) {
        $result['content_length'] = strlen($content);
    }

    // Check if successful
    if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
        $result['success'] = true;

        // Get first line of content (should be CSV header)
        $lines = explode("\n", $content, 2);
        $result['first_line'] = trim($lines[0] ?? '');

        // Validate it contains the expected header
        if (strpos($result['first_line'], $expectedHeader) === false) {
            $result['success'] = false;
            $result['error'] = "Response does not contain expected header: {$expectedHeader}";
        }
    } else {
        $result['error'] = "HTTP {$result['http_code']} response";

        // Include response body snippet for debugging
        if (strlen($content) > 0) {
            $result['error'] .= ': ' . truncate($content, 100);
        }
    }

    return $result;
}

/**
 * Format bytes to human readable
 */
function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

/**
 * Truncate string with ellipsis
 */
function truncate(string $str, int $length): string
{
    $str = str_replace(["\r", "\n"], ' ', $str);
    if (strlen($str) <= $length) {
        return $str;
    }
    return substr($str, 0, $length - 3) . '...';
}
