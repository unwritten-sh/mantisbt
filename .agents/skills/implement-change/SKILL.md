---
name: implement-change
description: Use when you take a MantisBT task (bug fix or small feature) from description to a verified change, using the fast and full gates.
---

# Implement a change in MantisBT

1. Find the issue on https://mantisbt.org/bugs. Every pull request must reference one (CONTRIBUTING.md). Note its number for the PR description (`Fixes #NNNN`, see `.github/pull_request_template.md`).
2. Make sure the tools are ready: `composer run setup`. It installs Composer and npm dependencies and the pre-push hook.
3. Locate the code. Use [docs/product.md](../../../docs/product.md) to map the use case to its entry points, and [docs/architecture.md](../../../docs/architecture.md) for the layers. Business rules go in `core/commands/` or `core/*_api.php`, not in a root page.
4. Write or extend a test first:
   - Pure logic that does not need `core.php`: add a test in `tests/Unit/` and run `vendor/bin/phpunit -c phpunit.unit.xml --filter <TestName>`.
   - Logic that needs the database: add a test in `tests/Mantis/` (extends `MantisCoreBase`). REST behaviour goes in `tests/rest/`.
5. Make the change. Follow the conventions in AGENTS.md: tabs, `p_`/`t_`/`c_`/`f_`/`g_` prefixes, `require_api()` for core modules, user-visible text through `lang_get()` (see the `add-language-string` skill).
6. Run the fast gate: `composer run check-fast`. Fix every failure. Do not skip or delete tests.
7. If the change touches the database, a command, the REST or SOAP API, or a page flow, run the full gate: `composer run check` (needs Docker, reinstalls the local instance). To run one integration test after an install: `vendor/bin/phpunit --filter <TestName>`.
8. If the schema changes, add an upgrade step at the end of `admin/schema.php`. Never edit existing steps.
9. Commit with a short imperative subject, as the history does (`Fix tag cache invalidation`, then `Test tag cache invalidation`).
10. Add an entry to `docs/harness/log.md` if you learned something that the next person needs. Open a pull request to `master`. Never push to `master` directly.
