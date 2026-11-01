# Contributing to MantisBT

First of all, we would like to thank you for considering helping us improve
MantisBT. We welcome contributions from anyone, and look forward to receiving
yours!

This document briefly describes our expectations with regards to your
submissions.


## Issue Tracker

We naturally use [MantisBT](https://mantisbt.org/) to track bugs and
feature requests, GitHub Issues are disabled.

Please report Issues at https://mantisbt.org/bugs.


## Pull Requests

We ❤️ Pull Requests! And even more so when they **respect the following rules**:

- The PR MUST target the **master** branch.
  We do not accept new features on stable releases.
  > [!NOTE]
  > When fixing a _confirmed_ Issue in the stable release, the PR can be
  > based on and target the corresponding branch (i.e. *master-X.Y*).
- Your feature branch must be up-to-date with the target branch
- The PR's description MUST **reference an existing issue** in our
  [Tracker](https://mantisbt.org/bugs/);
  please [create one](https://mantisbt.org/bugs/bug_report_page.php) if needed.
- Submissions MUST follow our
  **[coding guidelines](https://mantisbt.org/wiki/doku.php/mantisbt:coding_guidelines)**
- Commit messages SHOULD follow our [checklist](https://mantisbt.org/wiki/doku.php/mantisbt:git_commit_messages)

If updates to the target branch introduce merge conflicts in the PR, those
should genrerally be fixed by **rebasing** the feature branch, and then
**force-pushing** it, prior to merge.

Avoid force-pushing while code review is in progress.

> [!TIP]
> Do not hesitate to contact the MantisBT core team should you have any questions.
>
> [![Gitter](https://img.shields.io/gitter/room/mantisbt/mantisbt.svg?logo=gitter)](https://gitter.im/mantisbt/mantisbt)


## Local checks and definition of done

This section was added with the agent harness. It describes what the
repository shows; the core team should confirm it.

- Never push to `master` or a `master-X.Y` branch directly. Changes go through
  a pull request that a human reviews.
- Before you open a pull request, run `composer run check-fast` (PHP syntax
  check, OpenAPI lint, database-free unit tests). `composer run setup` installs
  a git pre-push hook that runs it.
- When you change behaviour, also run `composer run check`. It starts MySQL
  with Docker, installs MantisBT and runs all PHPUnit suites, like CI does.
  See [docs/local-dev.md](docs/local-dev.md).
- History shows short imperative commit subjects (`Fix tag cache
  invalidation`, `Test tag cache invalidation`, `GHA: ...`, `REST API: ...`),
  often a fix commit followed by a test commit. Pull requests are merged with a
  merge commit whose body links the PR (`PR https://github.com/mantisbt/mantisbt/pull/NNNN`).
- A pull request is done when: it references a mantisbt.org issue, the fast
  gate passes locally, CI is green, and a reviewer approved it.

> [!WARNING]
> **Needs a human.** What is the team's definition of done for a pull request beyond this file: how many reviewers, who merges, and rebase or merge commit?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/requirements/agreements/
<!-- uw-harness:todo agreements -->

> [!WARNING]
> **Needs a human.** May `.github/workflows/mantisbt.yml` call `composer run check` directly, or must the PHP x database matrix stay as it is? For now the harness adds a separate workflow, `.github/workflows/harness.yml`.
> Why it matters and how to do it: https://docs.unwritten.sh/harness/requirements/ci/
<!-- uw-harness:todo ci -->


## Further reading and references

- [Developers Guide](https://mantisbt.org/docs/master/en-US/Developers_Guide/html-desktop/)
- [MantisBT Wiki](https://mantisbt.org/wiki/doku.php)
