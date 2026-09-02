#!/usr/bin/env bash
# Generate release notes for a version.
# Extracts changelog entries from CHANGELOG.md and formats for GitHub Releases.
# Runs in GitHub Actions CI only.
set -euo pipefail

VERSION="${1:?Usage: release-notes.sh <version>}"
OUTPUT_FILE="${2:-release-notes.md}"

echo "==> Generating release notes for v$VERSION"

# Try to extract from CHANGELOG.md
if [ -f CHANGELOG.md ]; then
    # Extract section for this version (supports ## [X.Y.Z] or ## X.Y.Z)
    awk -v ver="$VERSION" '
        BEGIN { found=0; depth=0 }
        /^## / {
            if (found && depth == 0) exit
            if ($0 ~ "## \\[?" ver "\\]?") {
                found=1
                depth=0
                next
            }
        }
        found { print }
    ' CHANGELOG.md > "$OUTPUT_FILE"

    if [ -s "$OUTPUT_FILE" ]; then
        echo "==> Release notes extracted from CHANGELOG.md"
    else
        echo "==> No CHANGELOG.md entry found for v$VERSION, generating from git log"
        rm -f "$OUTPUT_FILE"
    fi
fi

# Fallback: generate from git log
if [ ! -s "$OUTPUT_FILE" ]; then
    PREVIOUS_TAG=$(git tag --sort=-v:refname | grep -v "$VERSION" | head -1 || echo "")

    if [ -n "$PREVIOUS_TAG" ]; then
        echo "## What's Changed in v$VERSION" > "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        git log "$PREVIOUS_TAG"..HEAD --pretty=format:"- %s (%h)" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "**Full Changelog**: https://github.com/reasvyn/internara/compare/$PREVIOUS_TAG...v$VERSION" >> "$OUTPUT_FILE"
    else
        echo "## Release v$VERSION" > "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        git log --pretty=format:"- %s (%h)" -20 >> "$OUTPUT_FILE"
    fi
fi

echo "==> Release notes written to $OUTPUT_FILE"
cat "$OUTPUT_FILE"