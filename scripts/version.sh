#!/bin/bash
# scripts/version.sh - Generate application version based on git status
#
# If APP_VERSION environment variable is already set, returns that value.
# Otherwise generates version from git:
#   - Uncommitted changes: dev-2026-01-14T13:45:00Z
#   - Committed, no tag: commit-abc1234
#   - Tagged commit: v1.2.3 or v1.3.0-beta

get_version() {
    # If APP_VERSION is already set in environment, use it
    if [ -n "$APP_VERSION" ]; then
        echo "$APP_VERSION"
        return
    fi

    # Check for uncommitted changes
    if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
        echo "dev-$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        return
    fi

    # Check if current commit has a tag
    TAG=$(git describe --tags --exact-match 2>/dev/null)

    if [ -z "$TAG" ]; then
        # No tag - return short commit hash with prefix
        HASH=$(git rev-parse --short HEAD 2>/dev/null)
        if [ -n "$HASH" ]; then
            echo "commit-$HASH"
        else
            echo "dev-$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        fi
        return
    fi

    # Return the tag as-is
    echo "$TAG"
}

get_version
