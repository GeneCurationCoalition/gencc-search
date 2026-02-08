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
ENDPOINT="/download/action/submissions-export-csv"
URL="${BASE_URL}${ENDPOINT}"

echo "Testing download endpoint: ${URL}"
echo "========================================"
echo

test_download() {
    local name="$1"
    local ua_option="$2"

    echo "Test: ${name}"

    # Build curl command
    if [ -z "$ua_option" ]; then
        # No User-Agent header at all
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -H "User-Agent:" "$URL")
    elif [ "$ua_option" = "EMPTY" ]; then
        # Empty User-Agent string
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -A "" "$URL")
    else
        # Specific User-Agent
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 -A "$ua_option" "$URL")
    fi

    if [ "$response" = "200" ]; then
        echo "  Status: ✓ PASS (HTTP $response)"
    else
        echo "  Status: ✗ FAIL (HTTP $response)"
    fi
    echo
}

# Test cases
test_download "With normal User-Agent" "GenCC-Test/1.0"
test_download "With empty User-Agent" "EMPTY"
test_download "With hyphen User-Agent" "-"
test_download "No User-Agent header" ""
test_download "Python requests" "python-requests/2.28.0"
test_download "curl default" "curl/7.88.0"
test_download "wget" "Wget/1.21"

echo "========================================"
echo "Done"
