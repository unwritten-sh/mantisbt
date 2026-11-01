# Harness brief: repo

Written by uw-harness 0.3.0 on 2026-11-01 with claude. One page for the people who decide what comes next. It is rewritten on every run: do not edit it by hand.

## What changed

Harness coverage went from 27 to 92 out of 100, level 0 to 2 (self-checking). The coverage score measures structure: the files exist with real content, the commands resolve, the gates ran tests. It does not measure how good the docs or the tests are.

## What was checked by running it

| Gate | Command | Result | Tests |
| --- | --- | --- | --- |
| fast | `composer run check-fast` | pass | 22 tests |
| full | `composer run check` | pass | 766 tests |

Before the harness, the project's tests could not be measured: `vendor/bin/phpunit` ran no test (exit 127). This says nothing about whether the tests pass, and the gates could not be compared with a starting point.

The test counts come from the output of the gates on the machine that ran uw-harness. They become proof when CI runs `npx uw-harness verify` on every pull request.

## What is left out or weaker

- Rules, files or tests excluded in lint, test or gate configuration: `.github/workflows/harness.yml` (`if: "!contains(github.event.head_commit.message, '[skip ci]'`).
- skills: The sandbox did not allow creating .claude/, so the .claude/skills -> ../.agents/skills symlink was not created. The skills are in .agents/skills/; the CLI must create the link.
- env-example: uw-harness detect flags lang/strings_portuguese_standard.txt as holding secret-looking values. They are UI labels (for example $s_password = 'Palavra-chave'), not secrets. The file is managed by translatewiki.net and was not changed, so env-example stays partial.
- ci: npx -y uw-harness verify returned 404 from the npm registry on 2026-11-01 (package not published). The new harness.yml workflow will fail until uw-harness is published; it was not run on GitHub. Locally the check was run with the uw-harness source checkout and passed.
- local-env: PostgreSQL support (DB_TYPE=pgsql, compose profile pgsql) was written but not run. Only MySQL was verified. The full gate drops and reinstalls the local database each time, and needs host port 3306 free because the mysqli driver ignores other ports.
- full-gate: The full gate does not build the DocBook guides or the SOAP WSDL HTML (documentation.yml needs Publican and xsltproc, not available as composer/npm dependencies). Everything else from CI (lint, api:validate, api:generate, all four PHPUnit suites) is in composer run check.

## Secret-looking values

1 file holds a value that looks like a secret. If they are real, a human must rotate them and remove them from the git history: `lang/strings_portuguese_standard.txt` (password or secret).

## Open questions

| Question | Who can answer | Where |
| --- | --- | --- |
| What is the team's definition of done for a pull request beyond this file: how many reviewers, who merges, and rebase or merge commit? | tech lead | `CONTRIBUTING.md`, [guide](https://docs.unwritten.sh/harness/requirements/agreements/) |
| May `.github/workflows/mantisbt.yml` call `composer run check` directly, or must the PHP x database matrix stay as it is? For now the harness adds a separate workflow, `.github/workflows/harness.yml`. | tech lead | `CONTRIBUTING.md`, [guide](https://docs.unwritten.sh/harness/requirements/ci/) |
| Is the project's own tracker at https://mantisbt.org/bugs deployed from this repository? If so, how is it upgraded, by whom, and on which branch? | whoever deploys to production | `docs/deploy.md`, [guide](https://docs.unwritten.sh/harness/requirements/deploy-explicit/) |
| Who can tag and sign a MantisBT release (which gpg key, see KEYS.md), and where are the release tarballs published (SourceForge, GitHub releases, mantisbt.org)? | whoever deploys to production | `docs/deploy.md`, [guide](https://docs.unwritten.sh/harness/requirements/deploy-explicit/) |
| Which database should be the default for local development and the harness gates: MySQL/MariaDB or PostgreSQL? | tech lead | `docs/local-dev.md`, [guide](https://docs.unwritten.sh/harness/requirements/local-env/) |
| Is a database-free unit suite (`tests/Unit`) the right fast gate for the pre-push hook, or should the fast gate also run `--testsuite mantis` against the Docker database? | tech lead | `docs/local-dev.md`, [guide](https://docs.unwritten.sh/harness/requirements/fast-gate/) |
| For any instance the team operates (for example https://mantisbt.org/bugs), where are the PHP error logs and MantisBT logs stored, who can read them, and is there any alerting or uptime monitoring? | whoever runs production | `docs/observability.md`, [guide](https://docs.unwritten.sh/harness/requirements/observability/) |

## The pilot task: Support short array syntax in ConfigParser

ConfigParser (core/classes/ConfigParser.class.php), used by adm_config_report.php to parse configuration values, only accepts array( ... ): the input "[ NEW_ => REPORTER ]" throws 'Unexpected token "["', although it is valid PHP. Add support for [ ... ] arrays, including nested ones and mixed with array( ... ), without using eval().

- Done when: ConfigParser::parse() returns array( NEW_ => REPORTER ) for the input "[ NEW_ => REPORTER ]" and array( 'a' => array( 1, 2 ) ) for "[ 'a' => [ 1, 2 ] ]".
- Done when: New tests for these cases and for an unclosed '[' (which must throw an Exception) are added to tests/Unit/ConfigParserUnitTest.php.
- Done when: composer run check-fast passes, and tests/Mantis/ConfigParserTest.php still passes in composer run check.

Run it with `npx uw-harness prove`: an agent does the task with the harness, on its own branch, and the result is added to this page.

## What this does not prove

It does not prove that agents can work on this repository without supervision. It proves that the knowledge is written down and that the gates run and run tests. No pilot task has been run yet. One task is one data point. Review the branch, answer the open questions, and judge the result yourself.
