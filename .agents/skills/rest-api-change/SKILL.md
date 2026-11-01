---
name: rest-api-change
description: Use when you add or change a MantisBT REST API endpoint, its request or response, or its OpenAPI description.
---

# Change the REST API

1. Read how a similar endpoint works. Routes are registered in `api/rest/restcore/<resource>_rest.php` (`issues`, `projects`, `users`, `filters`, `config`, `lang`, `pages`, `internal`). The app and the middlewares are set up in `api/rest/index.php`.
2. Put the business logic in a command class in `core/commands/` (extend `Command`, implement `validate()` and `process()`), or reuse an existing one. The route handler only builds the `query`/`payload`/`options` array, calls `execute()` and returns JSON. See [docs/decisions/0002-commands-shared-by-ui-and-rest.md](../../../docs/decisions/0002-commands-shared-by-ui-and-rest.md).
3. Signal errors with exceptions from `core/exceptions/` (`ClientException` for bad input, `StateException` for a wrong state). `api/rest/index.php` turns them into HTTP status codes.
4. Update the contract in `api/rest/mantisbt_openapi.yaml` in the same change. Then run `npm run api:validate`.
5. Add or extend a test in `tests/rest/` (extends `RestBase`) and, for the command, in `tests/Mantis/`.
6. Run `composer run check-fast`, then `composer run check`. The full gate starts the web server the REST tests need, and runs `npm run api:generate` to make sure the Postman, Bruno and Scalar outputs still build.
7. If the SOAP API exposes the same data (`api/soap/mc_*_api.php`), check that it still works: the `soap` suite runs in the full gate.
