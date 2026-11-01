---
name: add-language-string
description: Use when a change adds or edits text that users see in MantisBT (labels, messages, errors), so it goes through the language files.
---

# Add or change a user-visible string

1. Never write user-visible English text directly in a page or API. Pages call `lang_get( 'name' )` (`core/lang_api.php`).
2. Add the string to `lang/strings_english.txt` only, as `$s_<name> = '<text>';`, next to related strings. Use single quotes and escape apostrophes (`won\'t`).
3. Do not edit the other `lang/strings_*.txt` files. translatewiki.net updates them through automated commits (`Localisation updates from https://translatewiki.net. [skip ci]`), and manual edits are lost.
4. For an enum label, change both `$g_<name>_enum_string` in `config_defaults_inc.php` and `$s_<name>_enum_string` in `lang/strings_english.txt`. They must define the same values: `tests/Unit/ConfigDefaultsUnitTest.php` checks it.
5. For a new error code, add the constant in `core/constant_inc.php` and the message in the `$MANTIS_ERROR` array of `lang/strings_english.txt`.
6. Run `composer run check-fast`. `build/lint_php.sh` checks the syntax of the language file and the unit tests check the enums.
