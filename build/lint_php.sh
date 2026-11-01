#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# MantisBT harness - PHP syntax check (php -l) of every tracked PHP file.
#
# Read-only: it never rewrites files. vendor/ is not tracked, so it is skipped.
# Exit code is non-zero when at least one file has a syntax error.
# -----------------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")/.."

t_count=$(git ls-files '*.php' | wc -l | tr -d ' ')
t_errors=$(git ls-files -z '*.php' \
	| xargs -0 -n 1 -P 8 php -d error_reporting=E_ALL -d display_errors=stderr -l 2>/dev/null \
	| grep -v '^No syntax errors detected' || true)

if [ -n "$t_errors" ]; then
	echo "$t_errors"
	echo "php -l: syntax errors found (checked $t_count files)"
	exit 1
fi
echo "php -l: $t_count files, no syntax errors"
