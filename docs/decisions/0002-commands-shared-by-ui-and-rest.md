# 0002. Business actions live in command classes shared by the web UI and the APIs

Date: 2026-11-01

Status: Inferred from the code, to be confirmed by the team.

## Context

MantisBT has three ways to change data: the web pages in the repository root, the SOAP API in `api/soap/` and the REST API in `api/rest/`. When each entry point has its own copy of the validation and the business rules, the three drift apart.

## Decision

A business action is implemented once, as a class in `core/commands/` that extends `Command` (`core/commands/Command.php`). The class receives a data array (`query`, `payload`, `options`), checks it in `validate()` and does the work in `process()`. Callers run `execute()`.

The code shows this in use:

- `bug_report.php`, `api/rest/restcore/issues_rest.php` and `api/soap/mc_issue_api.php` all use `IssueAddCommand`.
- `bugnote_add.php` and the REST API use `IssueNoteAddCommand`.
- About 50 root PHP files and most `api/rest/restcore/*_rest.php` route handlers create command objects.

Errors are thrown as exceptions from `core/exceptions/` (`ClientException`, `StateException` and others). `api/rest/index.php` maps them to HTTP status codes.

## Consequences

- New write endpoints in the REST API should call an existing command or add a new one in `core/commands/`, not repeat logic from a page.
- Tests for business rules can target the command directly (see `tests/Mantis/IssueNoteAddCommandTest.php`, `tests/Mantis/IssueMoveCommandTest.php`).
- Older pages still call `core/*_api.php` functions directly. Both styles exist side by side.
