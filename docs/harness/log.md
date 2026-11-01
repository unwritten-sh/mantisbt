# Implementation log

One entry per significant change: date, what changed, what was learned, what is left.

## Notes from the harness build

- All existing PHPUnit suites (`mantis`, `soap`, `rest`, `mantis core formatting` in `phpunit.xml`) boot `core.php` and need an installed instance with a database. `soap` and `rest` also need a web server. They run only in the full gate (`composer run check` through `build/local_instance.sh test`). On 2026-11-01 they passed locally: 744 tests, PHP 8.5.3, MySQL 8.0 in Docker.
- The fast gate runs a new database-free suite, `tests/Unit` with `phpunit.unit.xml`. It loads single files (`core/classes/*.class.php`, `core/constant_inc.php`, `config_defaults_inc.php`, `lang/strings_english.txt`) without `core.php`.
- There was no PHP linter. `build/lint_php.sh` runs `php -l` on tracked PHP files. No coding-standard tool (phpcs, phpstan) was added, because there is no agreed rule set in the repository.
- `build/check_lineterm.php` is not in any gate: with options it rewrites files.
- `build/ci_install_mantis.sh` uses `declare -A` and needs bash 4. macOS ships bash 3.2, so `build/local_instance.sh` sends the same installer request with curl itself. It reuses `build/ci_post_install.sh` unchanged.
- MantisBT's `mysqli` driver always connects to port 3306, so `compose.yaml` maps MySQL to host port 3306.
- `npm run api:generate` runs `npm ci` in `tools/scalar` and `tools/bruno`, so the full gate needs network access. The fast gate does not.
- PostgreSQL support in `compose.yaml` and `build/local_instance.sh` (`DB_TYPE=pgsql`) was written but not run during the harness build.
- The existing CI matrix in `.github/workflows/mantisbt.yml` was not changed. `uw-harness verify` runs in a new workflow, `.github/workflows/harness.yml`, with PHP 8.1 and Node from `.nvmrc`.

## 2026-11-01: harness built by uw-harness 0.3.0 with claude

Score 27 → 92, level 0 → 2.

The harness uses composer.json scripts as the task runner. The fast gate (composer run check-fast, about 5 s) runs php -l on all tracked PHP files, the OpenAPI lint and a new database-free suite of 22 unit tests in tests/Unit. The full gate (composer run check, about 70 s) also generates the REST API artifacts and runs all 744 existing PHPUnit tests: build/local_instance.sh starts MySQL with compose.yaml, installs MantisBT through admin/install.php and reuses build/ci_post_install.sh. Both gates passed from a clean shell. Docs (architecture, product, ADRs, local-dev, deploy, observability), four skills, a pre-push hook, a new harness CI workflow and AGENTS.md were added, and the existing CI matrix was left unchanged. Seven open questions are TODO blocks, mainly about release publishing, the mantisbt.org instance and the team's review rules.

**Assessment.** A self-hosted, open-source web bug tracker written in PHP, with a web UI, a REST API, a legacy SOAP API and a plugin system. Stack: PHP (>= 8.1, composer platform 8.1.34), JavaScript (front-end in js/, Node tooling in tools/), SQL (via ADOdb), Shell (CI scripts in build/), Python (release build scripts in build/), DocBook XML (docbook/), Slim 3 (REST API), ADOdb (database abstraction), PHPMailer (email), HTMLPurifier and Parsedown (formatting), Guzzle (HTTP client), PHPUnit 10.5/11.5 (tests), Redocly CLI and openapi-to-postmanv2 (OpenAPI tooling), Bruno and Scalar (API docs tooling in tools/). The full assessment stays in `.git/uw-harness/assessment.md` on the machine that ran uw-harness.

Risks seen before the harness:

- All PHPUnit suites are integration tests: tests/TestConfig.php require_mantis_core() loads core.php, which needs config/config_inc.php and a database; soap and rest suites also need a running web server and an API token (created by tests/ci_create_api_token.php). A naive `vendor/bin/phpunit` on a fresh clone will fail, so the gates will look broken unless setup installs MantisBT first.
- phpunit.xml uses bootstrap=tests/bootstrap.php, which is not tracked (only bootstrap.php.sample); build/ci_post_install.sh appends to it. Setup must create it.
- phpunit.xml sets failOnAllIssues=true: any PHP deprecation or warning (for example on newer PHP versions) fails the run.
- build/check_lineterm.php rewrites files when run with options; do not use it as a read-only lint.
- No PHP linter or static analyser is configured (no phpcs, phpstan, php-cs-fixer); a lint gate must be added from scratch (php -l is the safe minimum).

**Before the harness**: `vendor/bin/phpunit`, ran no test (exit 127).

**Gates verified by the CLI**

- pass: `composer run setup`
- pass: `composer run check-fast` (22 tests)
- pass: `composer run check` (766 tests)

**Dependency and new files to review**

- A `build/lint_php.sh`: new file outside the usual harness paths
- A `build/local_instance.sh`: new file outside the usual harness paths
- M `composer.json`: dependency file
- M `package.json`: dependency file
- A `phpunit.unit.xml`: new file outside the usual harness paths

**Exclusions added by this branch to lint, test or gate configuration** (lint and test configuration files are in the separate commit for review; task runner and hook files stay in the harness commit):

- `.github/workflows/harness.yml`: `if: "!contains(github.event.head_commit.message, '[skip ci]')"`

**Secret-looking values already in the repository**

- `lang/strings_portuguese_standard.txt` (password or secret)

**First task to try with the harness:** Support short array syntax in ConfigParser. ConfigParser (core/classes/ConfigParser.class.php), used by adm_config_report.php to parse configuration values, only accepts array( ... ): the input "[ NEW_ => REPORTER ]" throws 'Unexpected token "["', although it is valid PHP. Add support for [ ... ] arrays, including nested ones and mixed with array( ... ), without using eval().

- Done when: ConfigParser::parse() returns array( NEW_ => REPORTER ) for the input "[ NEW_ => REPORTER ]" and array( 'a' => array( 1, 2 ) ) for "[ 'a' => [ 1, 2 ] ]".
- Done when: New tests for these cases and for an unclosed '[' (which must throw an Exception) are added to tests/Unit/ConfigParserUnitTest.php.
- Done when: composer run check-fast passes, and tests/Mantis/ConfigParserTest.php still passes in composer run check.

**Changed files**

- `composer.json` (task-runner): Added a scripts section (setup, dev, lint, test, test-integration, check-fast, check) with descriptions. Existing config and dependencies unchanged.
- `phpunit.unit.xml` (fast-gate): New PHPUnit config for the database-free unit suite tests/Unit (bootstrap vendor/autoload.php, failOnAllIssues like phpunit.xml).
- `tests/Unit/MantisEnumUnitTest.php` (tests): Database-free tests for MantisEnum (labels, values, unknown values, trimming, localized labels).
- `tests/Unit/ConfigParserUnitTest.php` (tests): Database-free tests for ConfigParser and Tokenizer (scalars, constants, threshold arrays, extra tokens, invalid PHP).
- `tests/Unit/ConfigDefaultsUnitTest.php` (tests): Consistency tests: every $g_*_enum_string in config_defaults_inc.php has an English $s_ translation with the same values; access level and status enums match constants.
- `build/lint_php.sh` (fast-gate): Read-only php -l over all tracked PHP files, fails on syntax errors.
- `build/local_instance.sh` (local-env): Bash 3.2 compatible script that mirrors CI locally: starts the DB with compose, runs admin/install.php, reuses build/ci_post_install.sh, serves the app, runs all phpunit.xml suites; also reset and down commands.
- `compose.yaml` (local-env): MySQL 8.0 (default) and optional PostgreSQL 16 (profile pgsql) with the CI credentials, project name mantisbt.
- `.env.example` (env-example): Documents the variables read by the local scripts and compose, and points to config/config_inc.php.sample and MANTIS_CONFIG_FOLDER for application settings.
- `.gitignore` (local-env): Ignore .phpunit.cache/ and .env.
- `.githooks/pre-push` (hooks): Pre-push hook running composer run check-fast; installed by composer run setup via core.hooksPath.
- `.github/workflows/harness.yml` (ci): New workflow on pull_request: PHP 8.1, Node from .nvmrc, runs npx -y uw-harness verify. Existing mantisbt.yml matrix untouched.
- `.tool-versions` (toolchain-pinned): php 8.1.34 (composer platform) and nodejs 22.12.0.
- `.nvmrc` (toolchain-pinned): Node 22, as required by @redocly/cli engines.
- `package.json` (toolchain-pinned): Added engines.node matching @redocly/cli requirements. Scripts unchanged.
- `docs/architecture.md` (architecture): Mermaid diagram plus sections for pages, core, commands, REST, SOAP, admin, plugins, scripts, database tables, config, lang, external services, tooling, and what is not visible.
- `docs/product.md` (product): Value proposition, roles mapped to access levels, 10 use cases with entry points, glossary.
- `docs/decisions/0001-record-architecture-decisions.md` (decisions): ADR format and rules.
- `docs/decisions/0002-commands-shared-by-ui-and-rest.md` (decisions): Inferred decision: business actions in core/commands shared by pages, REST and SOAP.
- `docs/local-dev.md` (local-env): Prerequisites, first run, daily run, one test, reset, debugging, common problems; TODOs for DB default and fast gate scope.
- `docs/deploy.md` (deploy-explicit): Branches, release build from build/README.md, install/upgrade from README.md, rollback, post-upgrade checks; TODOs for release publishing and mantisbt.org instance.
- `docs/observability.md` (observability): log_event settings and format, EVENT_LOG, PHP error_log, REST 500 logging, admin/check and email queue, signs of trouble; TODO for production logs and alerting.
- `docs/harness/log.md` (implementation-log): Notes from the harness build: which tests need DB, lint choice, bash 3.2 workaround, port 3306, network needs, pgsql not verified.
- `CONTRIBUTING.md` (agreements): Added a 'Local checks and definition of done' section (gates, no direct push, commit and merge style from history) with TODOs for review rules and CI changes. Existing rules untouched.
- `.agents/skills/implement-change/SKILL.md` (skills): From mantisbt.org issue to verified PR using the fast and full gates.
- `.agents/skills/debug-local/SKILL.md` (skills): Reproduce, log settings, DB shell, fake mail, Xdebug, reset.
- `.agents/skills/rest-api-change/SKILL.md` (skills): Route + command + exceptions + OpenAPI spec + rest tests + gates.
- `.agents/skills/add-language-string/SKILL.md` (skills): Edit only strings_english.txt, enum and error string rules.
- `AGENTS.md` (agents-file): 86-line index: what it is, commands, layout, conventions, verify, never, links.
- `CLAUDE.md` (agents-file): Symlink to AGENTS.md.

**Open questions for a human**

- Is the project's own tracker at https://mantisbt.org/bugs deployed from this repository? If so, how is it upgraded, by whom, and on which branch? (in `docs/deploy.md`)
- Who can tag and sign a MantisBT release (which gpg key, see KEYS.md), and where are the release tarballs published (SourceForge, GitHub releases, mantisbt.org)? (in `docs/deploy.md`)
- For any instance the team operates (for example https://mantisbt.org/bugs), where are the PHP error logs and MantisBT logs stored, who can read them, and is there any alerting or uptime monitoring? (in `docs/observability.md`)
- What is the team's definition of done for a pull request beyond CONTRIBUTING.md: how many reviewers, who merges, and rebase or merge commit? (in `CONTRIBUTING.md`)
- May .github/workflows/mantisbt.yml call composer run check directly, or must the PHP x database matrix stay as it is? For now the harness adds a separate workflow, .github/workflows/harness.yml. (in `CONTRIBUTING.md`)
- Which database should be the default for local development and the harness gates: MySQL/MariaDB or PostgreSQL? (in `docs/local-dev.md`)
- Is a database-free unit suite (tests/Unit) the right fast gate for the pre-push hook, or should the fast gate also run --testsuite mantis against the Docker database? (in `docs/local-dev.md`)

**Left undone**

- skills: The sandbox did not allow creating .claude/, so the .claude/skills -> ../.agents/skills symlink was not created. The skills are in .agents/skills/; the CLI must create the link.
- env-example: uw-harness detect flags lang/strings_portuguese_standard.txt as holding secret-looking values. They are UI labels (for example $s_password = 'Palavra-chave'), not secrets. The file is managed by translatewiki.net and was not changed, so env-example stays partial.
- ci: npx -y uw-harness verify returned 404 from the npm registry on 2026-11-01 (package not published). The new harness.yml workflow will fail until uw-harness is published; it was not run on GitHub. Locally the check was run with the uw-harness source checkout and passed.
- local-env: PostgreSQL support (DB_TYPE=pgsql, compose profile pgsql) was written but not run. Only MySQL was verified. The full gate drops and reinstalls the local database each time, and needs host port 3306 free because the mysqli driver ignores other ports.
- full-gate: The full gate does not build the DocBook guides or the SOAP WSDL HTML (documentation.yml needs Publican and xsltproc, not available as composer/npm dependencies). Everything else from CI (lint, api:validate, api:generate, all four PHPUnit suites) is in composer run check.
