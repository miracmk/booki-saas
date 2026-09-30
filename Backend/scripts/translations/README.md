# BooKi translation dictionaries

## How the pieces fit together

| Layer | File | Responsibility |
| --- | --- | --- |
| English master | `application/language/english/translations_lang.php` | Single source of truth for every key |
| Runtime guarantee | `application/core/App_Lang.php` | Fills every key a language dictionary is missing (and every key a dictionary left blank) from the English master of the *same* language file |
| Build-time translations | `scripts/translations/<lang>.php` | Real per-language translations, merged into the dictionaries by the sync tool |
| Sync tool | `scripts/sync_translations.php` | Merges the above into `application/language/<lang>/translations_lang.php` |
| Quality gate | `tests/Unit/TranslationsCoverageQualityTest.php` | Locks in the coverage guarantees and blocks regressions |

Because `App_Lang` tops up the runtime dictionary, a raw key token can never reach
the UI (neither through server-side `lang()` nor through the `window.lang()`
dictionary, which `App_Controller::load_common_html_vars()` publishes as the
`language` html var). The sync tool then converts those safe-but-English
fallbacks into real translations.

## Adding or updating translations

1. Edit (or create) `scripts/translations/<language>.php`. It must `return` a flat
   `key => value` array. Only keys that also exist in the English master are used,
   so a stale entry can never introduce a dangling key.
2. Run the tool:

   ```bash
   cd Backend
   php scripts/sync_translations.php                # every language
   php scripts/sync_translations.php --lang=german  # one language
   php scripts/sync_translations.php --check        # report only
   php scripts/sync_translations.php --strict       # fail (exit 1) on any gap
   php scripts/sync_translations.php --scaffold=thai  # TSV skeleton of what is missing
   ```

3. Verify:

   ```bash
   for f in $(git diff --name-only -- '*.php'); do php -l "$f"; done
   vendor/bin/phpunit --testsuite unit --filter TranslationsCoverageQualityTest
   ```

The generated block inside each dictionary is delimited by
`// @translation-sync:start` / `// @translation-sync:end` and is idempotent -
never hand-edit it, edit the source file and re-run the tool.

## Current status

Natively complete (zero gaps): **english, turkish, german, french, russian**.

All remaining languages are *behaviourally* complete thanks to the runtime
fallback, and are tracked in `TranslationsCoverageQualityTest::GAP_BASELINE`.
That baseline is a ratchet: adding translations lowers the real number and keeps
the test green, while deleting or renaming keys fails the build. When a language
reaches zero gaps, move it from `GAP_BASELINE` into `NATIVELY_COMPLETE_LANGUAGES`.
