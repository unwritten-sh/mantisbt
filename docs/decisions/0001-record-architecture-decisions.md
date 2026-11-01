# 0001. Record architecture decisions

Date: 2026-11-01

Status: Proposed (added with the agent harness, to be confirmed by the team)

## Context

MantisBT has more than 15,000 commits. Many design choices (procedural `core/*_api.php` modules, `$g_` configuration globals, command classes, ADOdb) are only visible in the code and in old discussions on the mantisbt.org tracker and mailing list. New contributors and coding agents cannot see why things are the way they are.

## Decision

We keep short architecture decision records (ADRs) in `docs/decisions/`.

- One Markdown file per decision, named `NNNN-short-title.md` with a four-digit number.
- Each file has: a title, a date, a status (Proposed, Accepted, Superseded by NNNN), and three sections: Context, Decision, Consequences.
- A record that was reconstructed from the code says so: "Inferred from the code, to be confirmed by the team."
- We do not edit an accepted record to change the decision. We write a new record that supersedes it.

## Consequences

- A pull request that changes a structural choice (a new layer, a new dependency, a new data store, a change to the API contract style) adds a record.
- The records are small and live next to the code, so they are reviewed in the same pull request.
