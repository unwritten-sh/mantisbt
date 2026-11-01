# Release and deployment

MantisBT is a product that other organisations install on their own servers. This repository does not deploy a running service. It produces release tarballs, and each installation upgrades itself with the web installer. This document covers what the repository shows about both.

## Environments and branches

- `master`: development of the next release. Pull requests target it (CONTRIBUTING.md).
- `master-X.Y` (for example `master-2.28`, `master-1.3.x`): stable branches. Fixes for a confirmed issue in a stable release can target them.
- `MANTIS_VERSION` in `core/constant_inc.php` holds the version of the code (`2.29.0-dev` on `master` at the time of writing).
- There is no staging or production environment described in this repository.

> [!WARNING]
> **Needs a human.** Is the project's own tracker at https://mantisbt.org/bugs deployed from this repository? If so, how is it upgraded, by whom, and on which branch?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/guides/deploy/
<!-- uw-harness:todo deploy-explicit -->

## How a release is built

From `build/README.md`:

1. Check line terminations from a developer machine: `php -q ./build/check_lineterm.php -c`. Note: this script can rewrite files, so it is not part of the gates.
2. Update `MANTIS_VERSION` in `core/constant_inc.php` and `Revision_History.xml` of the Admin Guide and the Developers Guide. Commit "Update release to x.y.z".
3. Tag on the official build machine (it has gpg, zip, tar, md5sum, sha1sum and Publican): `git tag -s release-$VERSION -m "Stable release $VERSION"`, then push the tag.
4. Build with `build/buildrelease-repo.py --fresh --docbook --ref release-$VERSION <output dir> <repo dir>`. It calls `build/buildrelease.py` and the DocBook scripts to produce the tarballs with the manuals.

The CI workflows (`.github/workflows/mantisbt.yml`, `documentation.yml`, `harness.yml`) test the code and the docs. None of them builds or publishes a release.

> [!WARNING]
> **Needs a human.** Who can tag and sign a MantisBT release (which gpg key, see KEYS.md), and where are the release tarballs published (SourceForge, GitHub releases, mantisbt.org)?
> Why it matters and how to do it: https://docs.unwritten.sh/harness/guides/deploy/
<!-- uw-harness:todo deploy-explicit -->

## How an installation is deployed and upgraded

From README.md, for administrators of an installation:

1. Back up the installation and the database.
2. Extract the tarball into a clean directory. Do not extract over an existing installation.
3. Copy `config/config_inc.php` and any `custom_*_inc.php` files from the old installation.
4. Open `admin/check/index.php` to check the server, then `admin/install.php` to create or upgrade the schema. `admin/upgrade_unattended.php` does the upgrade from the command line.
5. Remove the `admin/` folder.

Schema changes are steps in `admin/schema.php`. The installer runs the steps that are newer than the version stored in the database.

## How to roll back

The repository has no downgrade path for the schema. README.md tells administrators to back up the installation and the database before an upgrade. A rollback is a restore of both backups.

## What to check after an upgrade

- `admin/check/index.php` reports no failed check (run it before you remove `admin/`).
- Log in, open "View Issues" and one issue.
- If the REST API is used, call it with an API token.
