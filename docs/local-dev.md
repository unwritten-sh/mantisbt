# Local development

The database runs in Docker (`compose.yaml`). MantisBT runs on the host with PHP's built-in web server. `build/local_instance.sh` does the same steps as the CI workflow `.github/workflows/mantisbt.yml`.

## Prerequisites

- PHP 8.1 or later (`.tool-versions` pins 8.1.34, the Composer platform version). Extensions used in CI: `mysqli`, `pgsql`, `mbstring`, `json`, `tokenizer`, `curl`, `gd`, `fileinfo`, `ldap`, `soap`, `zlib`. `zip` is needed for `composer install` (dev requirement).
- Composer.
- Node.js 22 (`.nvmrc`) and npm, for the OpenAPI tooling.
- Docker with Docker Compose, for the database. Only the full gate and the dev server need it.
- `curl` and `bash`. The scripts work with the bash 3.2 that ships with macOS.
- Network access to GitHub for `composer install`: `dapphp/securimage` comes from the `mantisbt/securimage` repository.

## First run

```sh
composer run setup     # composer install, npm ci, git hooks (.githooks/pre-push)
composer run dev       # start MySQL, install MantisBT, serve http://127.0.0.1:8989/
```

Log in as `root` with password `root`. The install also creates "Test Project" with three versions, two tags and an `anonymous` user (`build/ci_post_install.sh`).

To change the port, the database type or the credentials, copy `.env.example` to `.env` and edit it. For PostgreSQL, set `DB_TYPE=pgsql`.

MySQL is the default because `$g_db_type` defaults to `mysqli` in `config_defaults_inc.php`. The harness was verified with MySQL only.

> [!WARNING]
> **Needs a human.** Which database should be the default for local development and the harness gates: MySQL/MariaDB or PostgreSQL?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/requirements/local-env/
<!-- uw-harness:todo local-env -->

## Daily run

```sh
composer run dev          # reuses the existing install
composer run check-fast   # lint + unit tests, no database (about 5 seconds)
composer run check        # full gate: reinstalls the local instance and runs all suites
```

Run one test:

```sh
vendor/bin/phpunit -c phpunit.unit.xml --filter MantisEnumUnitTest   # unit test, no database
vendor/bin/phpunit --filter StringTest                               # integration test, needs an installed instance
```

The fast gate runs only `tests/Unit` (through `phpunit.unit.xml`), because every suite in `phpunit.xml` boots `core.php` and needs a database.

> [!WARNING]
> **Needs a human.** Is a database-free unit suite (`tests/Unit`) the right fast gate for the pre-push hook, or should the fast gate also run `--testsuite mantis` against the Docker database?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/requirements/fast-gate/
<!-- uw-harness:todo fast-gate -->

Integration tests need `config/config_inc.php` and `tests/bootstrap.php`, which the install writes. The `soap` and `rest` suites also need the web server running on the URL written in `tests/bootstrap.php`. Run `composer run dev` in another terminal, or use `composer run test-integration`.

## Reset

`composer run check` and `composer run test-integration` always drop the database and reinstall. Your local data is lost.

```sh
build/local_instance.sh install --reset   # fresh database and config
build/local_instance.sh reset             # drop database, delete config/config_inc.php and tests/bootstrap.php
build/local_instance.sh down              # stop containers and delete the volumes
```

`build/ci_post_install.sh` appends to `config/config_inc.php` and `tests/bootstrap.php`, so do not run it twice on the same install. Reset first.

## Debugging

- Web server log: the script prints its path (`$TMPDIR/mantisbt-php-server.log`) when it starts a background server. `composer run dev` prints to the terminal.
- MantisBT log: add to `config/config_inc.php`, for example:

  ```php
  $g_log_level = LOG_ALL;
  $g_log_destination = 'file:/tmp/mantisbt.log';
  $g_show_detailed_errors = ON;
  ```

  `$g_log_destination = 'page'` shows the log at the bottom of each page. See `core/logging_api.php` and the comments in `config_defaults_inc.php`.
- Emails: the local server uses `tests/fakesendmail.sh` as `sendmail_path`. Messages are written to `$TMPDIR/fakemail/`.
- Breakpoints: start PHP with Xdebug. To debug REST calls made by the test suite, set `$GLOBALS['MANTIS_TESTSUITE_XDEBUG_SESSION']` in `tests/bootstrap.php` (see `tests/bootstrap.php.sample`).
- Database shell: `docker compose exec mysql mysql -uroot -proot bugtracker`.
- Environment check: open http://127.0.0.1:8989/admin/check/index.php.

## Common problems

- Port 3306 is busy: MantisBT's `mysqli` driver always connects to port 3306, so stop the other MySQL server.
- Port 8989 is busy: set `PORT` in `.env`.
- `Installer did not write config/config_inc.php`: read the web server log. The `config/` folder must be writable.
- Tests fail on a deprecation: `phpunit.xml` and `phpunit.unit.xml` set `failOnAllIssues="true"`, so PHP deprecations and warnings fail the run. Fix the cause.
- `composer install` fails on `dapphp/securimage`: check network access to github.com.
