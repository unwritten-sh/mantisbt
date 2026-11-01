#!/usr/bin/env bash
# The project's own test command (vendor/bin/phpunit, as in .github/workflows/mantisbt.yml) on the
# base commit. Like the upstream CI, it needs an installed MantisBT: the local files that the setup
# gate created (config/config_inc.php, tests/bootstrap.php) and a copy of vendor/ are taken from the clone.
# DIFF=<file> applies a diff first (the control arm).
set -u
repo="$PWD/repo"; d="$PWD/own-tree"
git -C "$repo" worktree remove --force "$d" 2>/dev/null; rm -rf "$d"
git -C "$repo" worktree add -q --detach "$d" "$(jq -r .base_sha meta.json)"
[ -n "${DIFF:-}" ] && git -C "$d" apply "$DIFF" && echo "applied $DIFF"
rm -rf "$d/vendor"; cp -R "$repo/vendor" "$d/vendor"
cp "$repo/config/config_inc.php" "$d/config/"; cp "$repo/tests/bootstrap.php" "$d/tests/"
cd "$d"; start=$(date +%s)
CI=1 php -d opcache.jit=disable vendor/bin/phpunit 2>&1 | tail -40
echo "exit ${PIPESTATUS[0]} seconds $(( $(date +%s) - start ))"
cd ..; git -C "$repo" worktree remove --force "$d"
