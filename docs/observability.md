# Observability

MantisBT has no metrics, tracing or error reporting service built in. It writes logs through PHP and offers admin pages to check the installation. This document describes what the code provides. Where the logs of a real instance are stored is not visible in this repository.

## Logs

- Application log: `log_event()` in `core/logging_api.php`. Two settings control it (defaults in `config_defaults_inc.php`):
  - `$g_log_level`: a bit mask of `LOG_*` constants from `core/constant_inc.php` (`LOG_EMAIL`, `LOG_EMAIL_RECIPIENT`, `LOG_FILTERING`, `LOG_AJAX`, `LOG_LDAP`, `LOG_DATABASE`, `LOG_WEBSERVICE`, `LOG_EMAIL_VERBOSE`, `LOG_PLUGIN`, or `LOG_ALL`). The default is `LOG_NONE`.
  - `$g_log_destination`: `''` (PHP `error_log` settings), `'none'`, `'file:/absolute/path.log'` or `'page'` (bottom of each page, for users at `$g_show_log_threshold` or above).
- Format: one line per event, `<time> <level> <caller> <message>`.
- Every log event also signals the `EVENT_LOG` plugin event, so a plugin can forward logs elsewhere.
- PHP errors: `core/error_api.php` handles errors and writes some of them with `error_log()`. `$g_display_errors` and `$g_show_detailed_errors` control what users see.
- REST API: `api/rest/index.php` writes unhandled exceptions with their stack trace to `error_log()` and answers HTTP 500. Known exceptions (`MantisException` and subclasses) become 4xx answers and are not logged.

## Health checks

- `admin/check/index.php`: checks PHP version and extensions, database, paths, email, crypto, attachments, plugins and web services. Use it after an install or upgrade. README.md says to remove `admin/` on live sites, so it is not a permanent health endpoint.
- `admin/email_queue.php`: shows emails waiting in `mantis_email_table`. A growing queue means `scripts/send_emails.php` is not running (when `$g_email_send_using_cronjob` is ON) or the mail server fails.
- There is no dedicated health or readiness URL. The login page (`login_page.php`) answers without authentication.

## How to tell that something is wrong

- PHP error log or MantisBT log file contains new errors or stack traces.
- REST API calls return HTTP 500.
- `mantis_email_table` keeps growing (see `admin/email_queue.php`).
- Users see the system error page (`core/error_api.php`).
- The site shows the offline page: `mantis_offline.php` exists in the root (see `mantis_offline.php.sample`).

## Where production logs and alerts live

> [!WARNING]
> **Needs a human.** For any instance the team operates (for example https://mantisbt.org/bugs), where are the PHP error logs and MantisBT logs stored, who can read them, and is there any alerting or uptime monitoring?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/guides/observability/
<!-- uw-harness:todo observability -->
