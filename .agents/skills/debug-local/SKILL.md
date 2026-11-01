---
name: debug-local
description: Use when a MantisBT page, API call or test fails locally and you need to reproduce it, read the logs, inspect the data and find the cause.
---

# Debug MantisBT locally

1. Start a local instance: `composer run dev`. It starts MySQL with Docker, installs MantisBT if `config/config_inc.php` is missing and serves http://127.0.0.1:8989/ (login `root` / `root`). Details in [docs/local-dev.md](../../../docs/local-dev.md).
2. Reproduce the problem:
   - Page: open it in the browser and keep the terminal of `composer run dev` visible. PHP errors appear there.
   - REST: create an API token on `api_tokens_page.php`, then `curl -H "Authorization: Bearer <token>" http://127.0.0.1:8989/api/rest/issues/1`.
   - Test: `vendor/bin/phpunit --filter <TestName>` (needs the install) or `vendor/bin/phpunit -c phpunit.unit.xml --filter <TestName>`.
3. Turn on MantisBT logging in `config/config_inc.php`: `$g_log_level = LOG_ALL;` and `$g_log_destination = 'file:/tmp/mantisbt.log';`. Add `$g_show_detailed_errors = ON;` to see stack traces in the page. Then `tail -f /tmp/mantisbt.log`.
4. Inspect the data: `docker compose exec mysql mysql -uroot -proot bugtracker`. Tables are named `mantis_<name>_table` (see [docs/architecture.md](../../../docs/architecture.md)). Settings changed in the UI are in `mantis_config_table`.
5. Check emails: the local server writes them to `$TMPDIR/fakemail/` through `tests/fakesendmail.sh`.
6. Use breakpoints when logs are not enough: run PHP with Xdebug. For REST calls made by tests, set `$GLOBALS['MANTIS_TESTSUITE_XDEBUG_SESSION']` in `tests/bootstrap.php`.
7. If the instance is in a bad state, start again: `build/local_instance.sh install --reset`.
8. When you find the cause, write a failing test first, then fix it with the `implement-change` skill.
