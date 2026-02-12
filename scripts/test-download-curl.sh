#!/bin/bash
#
# Test download endpoint with various User-Agent configurations
#
# Usage:
#   ./scripts/test-download-curl.sh [base_url]
#
# Examples:
#   ./scripts/test-download-curl.sh
#   ./scripts/test-download-curl.sh https://search.thegencc.org
#   ./scripts/test-download-curl.sh http://localhost:8000

BASE_URL="${1:-https://search.thegencc.org}"
# Legacy format is default (no parameter), new format requires ?format=new
ENDPOINT_LEGACY="/download/action/submissions-export-csv"
ENDPOINT_NEW="/download/action/submissions-export-csv?format=new"

test_download() {
    local name="$1"
    local ua_option="$2"
    local url="$3"

    echo "Test: ${name}"

    # Build curl command
    if [ -z "$ua_option" ]; then
        # No User-Agent header at all
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -H "User-Agent:" "$url")
    elif [ "$ua_option" = "EMPTY" ]; then
        # Empty User-Agent string
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -A "" "$url")
    else
        # Specific User-Agent
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -A "$ua_option" "$url")
    fi

    if [ "$response" = "200" ]; then
        echo "  Status: PASS (HTTP $response)"
    else
        echo "  Status: FAIL (HTTP $response)"
    fi
    echo
}

# Test legacy format (default - no parameter)
URL_LEGACY="${BASE_URL}${ENDPOINT_LEGACY}"
echo "Testing legacy format endpoint (default): ${URL_LEGACY}"
echo "========================================"
echo

test_download "Legacy: With normal User-Agent" "GenCC-Test/1.0" "$URL_LEGACY"
test_download "Legacy: With empty User-Agent" "EMPTY" "$URL_LEGACY"
test_download "Legacy: With hyphen User-Agent" "-" "$URL_LEGACY"
test_download "Legacy: No User-Agent header" "" "$URL_LEGACY"
test_download "Legacy: Python requests" "python-requests/2.28.0" "$URL_LEGACY"
test_download "Legacy: curl default" "curl/7.88.0" "$URL_LEGACY"
test_download "Legacy: wget" "Wget/1.21" "$URL_LEGACY"

# Test new format (requires ?format=new)
URL_NEW="${BASE_URL}${ENDPOINT_NEW}"
echo "Testing new format endpoint (?format=new): ${URL_NEW}"
echo "========================================"
echo

test_download "New: With normal User-Agent" "GenCC-Test/1.0" "$URL_NEW"
test_download "New: With empty User-Agent" "EMPTY" "$URL_NEW"
test_download "New: With hyphen User-Agent" "-" "$URL_NEW"
test_download "New: No User-Agent header" "" "$URL_NEW"
test_download "New: Python requests" "python-requests/2.28.0" "$URL_NEW"
test_download "New: curl default" "curl/7.88.0" "$URL_NEW"
test_download "New: wget" "Wget/1.21" "$URL_NEW"

echo "========================================"
echo "Done"
