# Product

MantisBT (Mantis Bug Tracker) is a self-hosted, open-source web bug tracker. Software teams use it to report, triage, assign and follow issues across many projects from a browser, with configurable workflows, access levels, custom fields and email notifications. Organisations install it on their own web server and database, so they keep control of their data. Integrators automate it through a REST API described in OpenAPI and a legacy SOAP API, and developers extend it with plugins.

The project tracks its own issues in a MantisBT instance at https://mantisbt.org/bugs (see [CONTRIBUTING.md](../CONTRIBUTING.md)).

## Users

Access levels are numbers defined in `core/constant_inc.php` and named in `$g_access_levels_enum_string`. Each action has a threshold setting that gives the minimum level.

| Role | Access level | What they get |
| --- | --- | --- |
| Anonymous viewer | none (when `$g_allow_anonymous_login` is ON) | Read-only access to public projects |
| Viewer | `VIEWER` (10) | Read access to issues in their projects |
| Reporter | `REPORTER` (25) | Report issues, add notes, monitor issues |
| Updater | `UPDATER` (40) | Edit issue fields |
| Developer | `DEVELOPER` (55) | Work on, assign and resolve issues |
| Manager | `MANAGER` (70) | Manage projects, versions, categories and project members |
| Administrator | `ADMINISTRATOR` (90) | Manage users, plugins, workflow and global configuration |

Two other groups use the code without a role in the UI: API integrators (scripts that call the REST or SOAP API with API tokens) and plugin developers (who extend MantisBT through events).

## Main use cases

| Use case | What happens | Entry points |
| --- | --- | --- |
| Report an issue | A user fills the report form (project, category, severity, description, attachments, custom fields). The issue is created and notifications are sent. | `bug_report_page.php`, `bug_report.php`, `core/commands/IssueAddCommand.php`, `api/rest/restcore/issues_rest.php` |
| View, filter and search issues | Users list issues with saved or ad-hoc filters, view details, history and relationships, and export them. | `view_all_bug_page.php`, `view_all_set.php`, `bug_view_page.php`, `core/filter_api.php`, `core/classes/BugFilterQuery.class.php`, `csv_export.php`, `excel_xml_export.php`, `api/rest/restcore/filters_rest.php` |
| Update and resolve issues | Developers change status, assignee, fields and versions, add notes, monitor, tag, relate and move issues. Bulk actions exist. | `bug_update_page.php`, `bug_update.php`, `bug_change_status_page.php`, `bugnote_add.php`, `bug_actiongroup.php`, `core/commands/IssueNoteAddCommand.php`, `core/commands/IssueMoveCommand.php`, `core/workflow_api.php` |
| Manage projects | Managers create projects and subprojects, versions, categories, custom fields and project membership. | `manage_proj_page.php`, `manage_proj_create.php`, `manage_proj_ver_add.php`, `manage_proj_cat_add.php`, `manage_custom_field_page.php`, `core/project_api.php`, `api/rest/restcore/projects_rest.php` |
| Manage users and API tokens | Administrators create, edit, impersonate and prune users. Users manage their account, preferences and API tokens. | `manage_user_page.php`, `manage_user_create.php`, `account_page.php`, `account_prefs_page.php`, `api_tokens_page.php`, `core/user_api.php`, `core/api_token_api.php`, `api/rest/restcore/users_rest.php` |
| Configure workflow, thresholds and email | Administrators set status transitions, access thresholds and email notification rules. Settings are stored in config files or in the database. | `manage_config_workflow_page.php`, `manage_config_work_threshold_page.php`, `manage_config_email_page.php`, `adm_config_report.php`, `config_defaults_inc.php`, `core/config_api.php`, `api/rest/restcore/config_rest.php` |
| Install and upgrade | The web installer checks the environment, creates or upgrades the schema and writes `config/config_inc.php`. | `admin/check/index.php`, `admin/install.php`, `admin/schema.php`, `admin/upgrade_unattended.php` |
| Integrate through the APIs | External tools create and read issues, projects, users, filters and config. | `api/rest/index.php`, `api/rest/mantisbt_openapi.yaml`, `api/soap/mantisconnect.php`, `api/soap/mantisconnect.wsdl` |
| Roadmap, changelog, summary and news | Users see the roadmap and changelog per version, statistics, timeline, news and RSS feeds. | `roadmap_page.php`, `changelog_page.php`, `summary_page.php`, `my_view_page.php`, `news_list_page.php`, `issues_rss.php`, `news_rss.php` |
| Extend with plugins and scheduled tasks | Plugins hook into events. A CLI cron script signals `EVENT_CRONJOB` and another sends queued emails. | `plugins/`, `core/plugin_api.php`, `core/events_inc.php`, `manage_plugin_page.php`, `scripts/cronjob.php`, `scripts/send_emails.php` |

## Glossary

| Term | Meaning |
| --- | --- |
| Bug / Issue | The tracked work item. Code and tables say "bug" (`bug_api.php`, `mantis_bug_table`). The UI says "issue". |
| Bugnote | A note or comment on an issue (`bugnote_api.php`, `mantis_bugnote_table`). |
| Access level / threshold | Numeric role (VIEWER to ADMINISTRATOR). A threshold is a setting that gives the minimum level for an action, for example `$g_update_bug_threshold`. |
| Workflow | Allowed status transitions (`$g_status_enum_workflow`, `manage_config_workflow_page.php`, `core/workflow_api.php`). |
| Enum string | A setting like `'10:new,20:feedback'` that maps numbers to labels (`MantisEnum` class). Each `$g_*_enum_string` has a translated `$s_*_enum_string` in `lang/`. |
| Filter | A saved or ad-hoc query over issues (`filter_api.php`, `BugFilterQuery`, `mantis_filters_table`). |
| Monitor | A user who follows an issue and receives notifications (`bug_monitor_add.php`). |
| Sponsorship | Optional feature where users pledge money on an issue (`sponsorship_api.php`). |
| Command | A class in `core/commands/` that implements one business action, shared by the web UI and the REST API. |
| MantisConnect (`mc_`) | The SOAP API. Its files in `api/soap/` start with `mc_`. Its `ApiObjectFactory` is reused by the REST API. |
| Event / plugin | Extension points declared in `core/events_inc.php` and signalled with `event_signal()`. |
| `g_` `p_` `f_` `c_` `t_` prefixes | Variable naming from README.md: global config, function parameter, form input, cleaned for the database, temporary. `s_` is a language string. |
| `config_inc.php` | Per-installation settings in `config/` (gitignored) that override `config_defaults_inc.php`. |
| `_page.php` / `_inc.php` | Root file naming: `*_page.php` renders a page, `*_inc.php` is an include fragment, other root files are form actions. |
| ADOdb | Database abstraction library (`adodb/adodb-php`) used by `core/database_api.php`. |
| Publican / DocBook | Toolchain and XML format of the Admin Guide and Developers Guide in `docbook/`. |
| translatewiki.net | External site that sends translation updates to `lang/` through automated commits. |
