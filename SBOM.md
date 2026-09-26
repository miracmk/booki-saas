# SBOM — BooKi (Software Bill of Materials)

Generated 2026-08-24. Lists every third-party dependency bundled with Ki
Reservation — PHP (via Composer), frontend JS/CSS (`assets/vendor/`), and
the underlying framework (`system/`). These components remain governed by
their own respective licenses (see [LICENSE](LICENSE) §5) — Ki Software
claims no ownership over them and redistributes them under their original
terms.

To regenerate the PHP table below after a dependency change, run (inside
the app container): the package list is read directly from
`vendor/composer/installed.json`, which Composer maintains automatically
on every `composer install`/`update`.

## PHP dependencies (Composer)

| Package | Version | License(s) | Description |
|---|---|---|---|
| `altcha-org/altcha` | v1.3.1 | MIT | Self-hosted, privacy-friendly CAPTCHA alternative |
| `ezyang/htmlpurifier` | v4.19.0 | LGPL-2.1-or-later | Standards-compliant HTML filter |
| `firebase/php-jwt` | v7.0.2 | BSD-3-Clause | JSON Web Token (JWT) encode/decode |
| `google/apiclient` | v2.19.0 | Apache-2.0 | Client library for Google APIs |
| `google/apiclient-services` | v0.433.0 | Apache-2.0 | Client library for Google APIs (service definitions) |
| `google/auth` | v1.50.0 | Apache-2.0 | Google Auth Library for PHP |
| `gregwar/captcha` | v1.3.0 | MIT | Captcha generator |
| `guzzlehttp/guzzle` | 7.10.0 | MIT | HTTP client library |
| `guzzlehttp/promises` | 2.3.0 | MIT | Promises library |
| `guzzlehttp/psr7` | 2.8.0 | MIT | PSR-7 HTTP message implementation |
| `jsvrcek/ics` | 0.8.6 | MIT | RFC 5545 (.ics) calendar file generation |
| `monolog/monolog` | 3.10.0 | MIT | Logging library |
| `paragonie/constant_time_encoding` | v3.1.3 | MIT | Constant-time base16/32/64 encoding |
| `paragonie/random_compat` | v9.99.100 | MIT | `random_bytes()`/`random_int()` polyfill |
| `phpmailer/phpmailer` | v7.0.2 | LGPL-2.1-only | Email creation and SMTP transfer |
| `phpseclib/phpseclib` | 3.0.49 | MIT | Pure-PHP RSA/AES/SSH2/SFTP/X.509 |
| `psr/cache` | 3.0.0 | MIT | PSR-6 caching interface |
| `psr/http-client` | 1.0.3 | MIT | PSR-18 HTTP client interface |
| `psr/http-factory` | 1.1.0 | MIT | PSR-17 HTTP message factory interfaces |
| `psr/http-message` | 2.0 | MIT | PSR-7 HTTP message interface |
| `psr/log` | 3.0.2 | MIT | PSR-3 logging interface |
| `ralouphie/getallheaders` | 3.0.3 | MIT | `getallheaders()` polyfill |
| `sabre/uri` | 3.0.2 | BSD-3-Clause | URI parsing utilities |
| `sabre/vobject` | 4.5.8 | BSD-3-Clause | iCalendar/vCard parsing and generation |
| `sabre/xml` | 4.0.6 | BSD-3-Clause | XML reader/writer library |
| `symfony/deprecation-contracts` | v3.6.0 | MIT | Deprecation notice helper |
| `symfony/finder` | v6.4.33 | MIT | File/directory finder |

## Frontend dependencies (`assets/vendor/`)

Pre-built distribution assets, not managed via package.json in this
repository. Versions correspond to vendored frontend packages.

| Library | License |
|---|---|
| jQuery | MIT |
| Bootstrap | MIT |
| @popperjs/core | MIT |
| Font Awesome Free | Font Awesome Free License (icons: CC BY 4.0, fonts: SIL OFL 1.1, code: MIT) |
| FullCalendar | MIT |
| FullCalendar Moment plugin | MIT |
| Moment.js | MIT |
| Moment Timezone | MIT |
| Flatpickr | MIT |
| Select2 | MIT |
| Tippy.js | MIT |
| Trumbowyg | MIT |
| CookieConsent | MIT |
| jQuery Jeditable | MIT |

## Framework (`system/`)

| Component | License |
|---|---|
| CodeIgniter (core framework) | MIT |

## Notes

- No known copyleft-obligation conflicts: the two LGPL components
  (`ezyang/htmlpurifier`, `phpmailer/phpmailer`) are used as unmodified
  libraries (dynamically included, not modified/relinked in a way that
  would trigger LGPL's stricter provisions).
- This SBOM should be regenerated whenever `composer.json`/`composer.lock`
  changes materially (new dependency, major version bump), and reviewed
  as part of any formal ISO 27001 / SOC 2 supply-chain assessment.
