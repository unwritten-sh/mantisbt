# AGENTS.md

MantisBT (Mantis Bug Tracker) is a self-hosted, open-source web bug tracker written in PHP. It has server-rendered pages, a REST API (Slim 3, OpenAPI), a legacy SOAP API and a plugin system, on MySQL or PostgreSQL through ADOdb.

## Commands

Run from the repository root. Composer scripts in `composer.json` are the task runner.

| Task | Command |
| --- | --- |
| Setup (deps + git hooks) | `composer run setup` |
| Run locally (Docker MySQL + PHP server on http://127.0.0.1:8989/) | `composer run dev` |
| Lint (php -l + OpenAPI lint) | `composer run lint` |
| Unit tests (no database) | `composer run test` |
| Fast gate | `composer run check-fast` |
| Full gate (what CI runs, needs Docker) | `composer run check` |
| Integration suites only | `composer run test-integration` |
| Validate / generate REST API artifacts | `npm run api:validate`, `npm run api:generate` |

One test:

- Unit, no database: `vendor/bin/phpunit -c phpunit.unit.xml --filter MantisEnumUnitTest`
- Integration, after `build/local_instance.sh install`: `vendor/bin/phpunit --filter StringTest` (the `soap` and `rest` suites also need `composer run dev` running)

Local instance helpers: `build/local_instance.sh install --reset`, `build/local_instance.sh reset`, `build/local_instance.sh down`. Details in [docs/local-dev.md](docs/local-dev.md).

## Layout

| Path | What lives there |
| --- | --- |
| `*.php` (root) | One file per page (`*_page.php`), include fragment (`*_inc.php`) or form action |
| `core.php` | Bootstrap loaded by every request: config, database, core APIs |
| `core/` | Business logic: `*_api.php` modules, `classes/`, `commands/` (shared by UI and REST), `exceptions/` |
| `api/rest/` | REST API: `index.php`, `restcore/*_rest.php` routes and middlewares, `mantisbt_openapi.yaml` |
| `api/soap/` | SOAP API (MantisConnect): `mantisconnect.php`, `mc_*_api.php`, WSDL |
| `admin/` | Installer, schema upgrades (`schema.php`), environment checks |
| `config_defaults_inc.php` | Default value and PHPDoc of every `$g_` setting |
| `config/` | Per-install `config_inc.php` (gitignored, written by the installer) and samples |
| `lang/` | `strings_<language>.txt`; only `strings_english.txt` is edited here |
| `plugins/` | Bundled plugins: MantisCoreFormatting, MantisGraph, XmlImportExport, Gravatar |
| `scripts/` | CLI jobs: `cronjob.php`, `send_emails.php` |
| `tests/` | `Unit/` (no database), `Mantis/` (core, needs DB), `soap/`, `rest/` (need web server) |
| `build/` | Release scripts, CI install scripts, `lint_php.sh`, `local_instance.sh` |
| `docbook/`, `tools/` | Admin and Developers guides; OpenAPI, Bruno, Scalar tooling |
| `vendor/`, `library/` | Third-party code. Do not edit |

## Conventions

- Indent with tabs (README.md). Spaces inside parentheses: `if( $t_x ) {`, `foo( $p_a, $p_b )`.
- Variable prefixes (README.md): `g_` global config, `p_` function parameter (do not modify), `f_` form input, `c_` cleaned for the database, `t_` temporary, `s_` language string.
- Load core modules with `require_api( 'bug_api.php' )`. Read request input with `gpc_get_*()` (`core/gpc_api.php`). Protect forms with `form_security_*()` (`core/form_api.php`).
- New business actions go in `core/commands/` and are called by both the page and the REST route ([decision 0002](docs/decisions/0002-commands-shared-by-ui-and-rest.md)).
- User-visible text goes through `lang_get()` and `lang/strings_english.txt`.
- Schema changes are new steps appended to `admin/schema.php`.
- REST changes update `api/rest/mantisbt_openapi.yaml` in the same change.
- `phpunit.xml` and `phpunit.unit.xml` set `failOnAllIssues`: a PHP deprecation or warning fails the tests.
- Commits: short imperative subject (`Fix tag cache invalidation`). Pull requests target `master` and reference a mantisbt.org issue. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Verify a change

1. Run `composer run check-fast` before you say a change is done. It must pass.
2. If you touched database code, commands, pages, the REST or SOAP API, run `composer run check` (reinstalls the local instance and runs 700+ integration tests).
3. Add tests: `tests/Unit/` when the code works without `core.php`, otherwise `tests/Mantis/` or `tests/rest/`.
4. After each significant change, add an entry to [docs/harness/log.md](docs/harness/log.md): date, what changed, what you learned, what is left.

## Never

- Never push to `master` or `master-X.Y`. Open a pull request for human review.
- Never edit `lang/strings_*.txt` other than `strings_english.txt` (translatewiki.net overwrites them).
- Never edit `vendor/` or `library/`, or commit `config/config_inc.php`, `.env` or `tests/bootstrap.php`.
- Never edit existing steps in `admin/schema.php`.
- Never run `build/check_lineterm.php` with options in a gate: it rewrites files.
- Never skip, delete or weaken a test to make a gate pass.
- Never run `composer run check` when the local database holds data you need: it drops it.

## More

- [docs/architecture.md](docs/architecture.md): components, tables, external services
- [docs/product.md](docs/product.md): users, use cases with entry points, glossary
- [docs/local-dev.md](docs/local-dev.md): prerequisites, first run, reset, debugging
- [docs/deploy.md](docs/deploy.md): releases and upgrades (open questions marked)
- [docs/observability.md](docs/observability.md): logs, health checks (open questions marked)
- [docs/decisions/](docs/decisions/0001-record-architecture-decisions.md): architecture decision records
- [CONTRIBUTING.md](CONTRIBUTING.md): pull request rules and definition of done
- Skills in `.agents/skills/`: `implement-change`, `debug-local`, `rest-api-change`, `add-language-string`
- Developers Guide: https://mantisbt.org/docs/master/en-US/Developers_Guide/html-desktop/
