#!/bin/bash
export GITHUB_PERSONAL_ACCESS_TOKEN=$(gh auth token 2>&1)
# fallback to GITHUB_TOKEN env if gh fails
if [ -z "$GITHUB_PERSONAL_ACCESS_TOKEN" ] && [ -n "$GITHUB_TOKEN" ]; then
  export GITHUB_PERSONAL_ACCESS_TOKEN="$GITHUB_TOKEN"
fi
exec npx -y @modelcontextprotocol/server-github "$@"
