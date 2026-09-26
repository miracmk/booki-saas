# BooKi — Feature Specification

Version 1.0.0 · Last updated 2026-08-24

This document is the single source of truth for what BooKi does
today, how it's built, and where it's headed. It's meant to be read by
Ki Software engineers picking up the project, and by prospective
licensees evaluating it.

---

## 1. What it is

BooKi is a self-hosted appointment/booking platform for service
businesses that need more than a generic calendar: physical resource
(room/station) management, staff commission tracking, session/payment
tracking, and compliance-grade handling of customer data. It is
engineered and built as an enterprise-grade booking and multi-tenant SaaS platform
under the Ki Software License (see [LICENSE](LICENSE)).

---

## 2. Core scheduling (inherited + hardened)

- Public booking wizard (service → provider → date/time → customer info →
  confirmation), with real-time availability that accounts for provider
  working hours, breaks, existing appointments, and (see §3) station
  occupancy.
- Backend calendar (day/week/month/table views) for admins, secretaries,
  and providers, with role-scoped visibility (a provider sees only their
  own appointments; a secretary sees their assigned providers').
- Multi-attendant services, working-plan exceptions, unavailability
  blocks.
- Email notifications (appointment saved/deleted, account recovery,
  password reset) with a per-recipient-role (customer/admin/
  secretary/provider), HTML-editable template system — including a
  WYSIWYG editor with a browser-round-trip-safe conditional-block syntax
  (`<!--{{#if field}}-->…<!--{{/if}}-->`) so editing a template can't
  silently corrupt its structure.
- Google Calendar sync, CalDAV sync, Jitsi/Google Meet link generation.

## 3. Resource & station management

- Physical stations/rooms (`stations` table), each assignable to one or
  more providers and one or more services.
- **Fail-open by default**: a station with no explicit service
  restriction is available to every service; restrictions are opt-in per
  provider (`station_restriction_enabled`).
- Real-time station availability check on booking (a provider is only
  unbookable at a given time if *every* one of their assigned/eligible
  stations is occupied by another appointment — not just their own).
- Race-condition-safe station assignment: concurrent booking requests for
  the same station are serialized via MySQL named locks
  (`GET_LOCK`/`RELEASE_LOCK`), closing a TOCTOU window where two
  customers could both be assigned the same physical room.
- Manual station override in the appointment modal, with automatic
  re-validation if the appointment's time or service changes after a
  manual pick.

## 4. Session tracking (check-in / check-out)

- Live session status on the calendar (not started / in progress /
  ending soon / overdue / done), computed client-side from
  `actual_start_datetime`/expected duration, with a 30-second ticker.
- One-click check-in/check-out from the calendar popover, the
  appointment modal, or the "Active Sessions" widget (visible from any
  backend page, not just the calendar).
- Deviation detection: a check-out significantly early or late (default:
  5 minutes or 10% of expected duration) requires the admin/secretary to
  record a reason before the session closes — providers cannot bypass
  this or backdate their own check-in/out times.
- Manual check-in/out time correction (admin/secretary only), since
  reports and hourly billing derive directly from these timestamps.

## 5. Commission & billing

- Per-provider default commission (percentage / fixed / hourly), with
  optional per-service override and a one-time overtime bonus (hourly
  type, sessions over 60 minutes).
- Duration-based billing: actual (check-in→check-out) duration rounds
  down to the nearest half hour (configurable via
  `SESSION_BILLING_ROUND_MINUTES`) and drives both the customer's price
  and the provider's payout — computed once, server-side
  (`compute_effective_billing()`), with an equivalent client-side preview
  for the "estimated charge" shown live in the appointment modal.
- Per-appointment custom duration / fixed price override (admin/secretary
  only).
- Daily revenue report: per-provider session count, worked time,
  collected/outstanding payment totals, invoiced count, and computed
  payout — all derived server-side from the same billing function used
  at checkout, so the report can never drift from what was actually
  charged.

## 6. Payment tracking

- `payment_status` (pending / collected / not_collected),
  `payment_method` (IBAN / physical POS / virtual POS / cash),
  `payment_amount`, `payment_balance_amount`, `is_invoiced` on every
  appointment.
- Only admins/secretaries can record or edit payment info — enforced
  server-side (a provider gets a 403, not just a hidden UI element).
- Mandatory (non-dismissible) payment-recording prompt on check-out for
  admins/secretaries, skippable only if payment was already recorded
  during the session.
- Persistent "payment missing" indicator (red badge on the calendar
  event, dedicated section in the Active Sessions widget) for any
  session that ended today without payment recorded — stays visible
  until resolved, even after the session drops out of "active."

## 7. Security

- **PII encryption at rest**: `email`, `phone_number`, `address`,
  `state`, `zip_code`, `notes` are AES-256-GCM encrypted for every
  `users` row (customers, providers, admins, secretaries alike).
  `first_name`/`last_name` are deliberately left in plaintext so
  day-to-day partial-name search keeps working.
- **Exact-match search index**: `email_hash`/`phone_number_hash`
  (HMAC-SHA256, case/whitespace-normalized) let staff still search by a
  complete phone number or email even though the field itself is
  encrypted. Partial search on those two fields, and any search on
  address/notes, is an accepted, documented tradeoff (see
  [KEY_MANAGEMENT.md](KEY_MANAGEMENT.md) for the underlying crypto
  design and rotation procedure).
- **Role-based data visibility**: providers only ever see a customer's
  first name — never phone/email/address/notes — enforced both in the
  UI and, independently, server-side (`filter_customer_for_role()`), so
  a provider inspecting network requests directly can't see more than
  the UI shows.
- **Authentication & authorization**: every state-changing endpoint
  checks `can()`/`cannot()` against the caller's role; several endpoints
  found unauthenticated during a 2026-08-24 security review (see
  project history) were closed.
- **Audit log** (`audit_log` table, viewable at Settings → Denetim
  Kayıtları, admin-only): records every login attempt (success/failure),
  logout, customer/provider deletion or right-to-erasure anonymization
  (including the automated nightly retention job, distinguishable by a
  null actor), and every payment record change — each with actor,
  role, timestamp, IP, and a small non-PII detail blob.
- **Content-Security-Policy** and standard security headers
  (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, HSTS) on every response.
- **Encrypted backups**: the nightly database backup is AES-256-CBC
  encrypted at rest before it ever touches disk (see
  [KEY_MANAGEMENT.md](KEY_MANAGEMENT.md)) — not yet copied offsite (see
  [COMPLIANCE.md](COMPLIANCE.md) §2).

## Licensing

BooKi is **SaaS-only** — there is no self-hosted distribution
and no offline license-key mechanism. Entitlement (plan, trial, license
expiry) is tracked per tenant in the master database (`tenants.plan` /
`trial_ends_at` / `license_expires_at`) and managed from the superadmin
panel (`admin-bookiapp.kibusiness.co`); `EA_Controller::resolve_tenant()`
enforces it. A self-hosted RS256-signed-JWT licensing system existed
briefly (2026-08-24 to 2026-09-11) and was removed - it was a leftover
self-hosted-product concept that never had a key-issuing tool and
collided with the actual SaaS plan system above, permanently showing a
"no license" warning on every tenant. May be revisited if a self-hosted
offering is reintroduced.

## 8. Data retention & KVKK/privacy compliance

- Configurable automated retention (`data_retention_days`, default 1825
  = 5 years): customers with no appointment activity within the window
  and whose account itself is older than the window are anonymized
  in-place (name → placeholder, all PII fields nulled) every night —
  **never row-deleted**, because appointment/payment history must
  survive for accounting purposes (VUK-aligned) and appointment rows are
  `ON DELETE CASCADE` from the customer row.
- Manual "KVKK - Unutulma Hakkı" (right to erasure) button on the
  Customers and Providers pages, calling the same anonymization path on
  demand.
- Consent tracking: the (stock, previously dormant) terms-and-conditions
  / privacy-policy checkbox mechanism on the public booking form is
  active, backed by a KVKK-oriented Aydınlatma Metni (Illumination Text)
  draft — **not reviewed by outside counsel; treat as a starting point**,
  not a finished legal document.

## 9. Architecture

| Layer | Technology |
|---|---|
| Application | PHP 8.2, CodeIgniter 3-style MVC (`system/`) |
| Database | MySQL 8 |
| Frontend | jQuery, Bootstrap 5, FullCalendar, Select2, Flatpickr |
| Email | PHPMailer over SMTP |
| Deployment | Docker (see root `Dockerfile.ki-reservation` in the deploying project) |
| Encryption | AES-256-GCM (OpenSSL), HMAC-SHA256 |

See [SBOM.md](SBOM.md) for the full third-party dependency list and
licenses, [KEY_MANAGEMENT.md](KEY_MANAGEMENT.md) for the encryption
key storage/rotation model, and [COMPLIANCE.md](COMPLIANCE.md) for the
ISO/IEC 27001 and SOC 2 gap analysis.

---

## 10. Known limitations (as of 1.0.0)

- Partial search (substring match) does not work on encrypted fields
  (phone/email support exact-match only; address/notes have no search at
  all). This is an intentional tradeoff, not a bug — see §7.
- `Unavailabilities_model::search()` still attempts a `LIKE` against
  encrypted provider email/phone; it silently returns no matches on
  those columns rather than erroring. Low-impact, not yet fixed.
- No dedicated KMS/HSM — encryption keys are environment-variable-based,
  appropriate for a single-server trust boundary but not yet for a
  multi-tenant SaaS posture (see KEY_MANAGEMENT.md §"Upgrading to a real
  KMS").
- No automated test suite specific to the BooKi customizations
  (the bundled `phpunit` dev dependency is inherited from upstream, not
  wired to the customized code paths).
- Single-maintainer change process — no formal code review gate on the
  private GitHub repository yet.
- The base Docker image's own `docker-entrypoint.sh` (not part of this
  repository) still writes a stray `Easy!Appointments` SMTP User-Agent
  header on outgoing mail — invisible to end users, cosmetic, unresolved
  pending an entrypoint override.
- The consent/privacy-policy text (§8) needs legal review before it can
  be relied on as the business's actual compliance position.

---

## 11. Roadmap / development ideas

Rough priority order, not commitments. Use this section to plan the next
round of work rather than rediscovering gaps from scratch each time.

### Near-term (hardening what exists)

- [ ] **MFA/2FA for admin accounts** (TOTP) — the single largest
      authentication gap identified in [COMPLIANCE.md](COMPLIANCE.md) §2.
- [ ] **Written incident response plan** — a short runbook for the first
      hour of a suspected breach (COMPLIANCE.md §2).
- [ ] **Offsite/immutable backup and log copy** — today both live only on
      the production host, so a host compromise can destroy the evidence
      of itself (COMPLIANCE.md §2).
- [ ] Legal review of the KVKK Aydınlatma Metni and the Ki Software
      License itself.
- [ ] Fix `Unavailabilities_model::search()`'s dead LIKE clauses on
      encrypted columns (§10).
- [ ] Override the base image's `docker-entrypoint.sh` to stop
      hardcoding "Easy!Appointments" in the SMTP User-Agent.
- [ ] Add automated regression tests for the highest-risk custom logic:
      station conflict resolution, `compute_effective_billing()`,
      PII encrypt/decrypt round-trip, audit log write paths.
- [ ] Extend the audit log to cover settings changes (email templates,
      retention period, integrations) — currently only auth/erasure/
      payment actions are logged.
- [ ] Add a "download my data" (data portability) export, the natural
      companion to the existing right-to-erasure button — KVKK/GDPR both
      expect both.

### Medium-term (product depth)

- [ ] SMS notifications (currently email-only) — a common ask for
      no-show reduction in salon/clinic verticals.
- [ ] Multi-location support (today's station model is single-location;
      a business with multiple branches would need location as a first-
      class dimension above stations).
- [ ] Customer self-service portal (view/reschedule/cancel own upcoming
      appointments without staff involvement, beyond the existing
      hash-link reschedule/cancel flow).
- [ ] Inventory/retail add-on tracking per appointment (product sales
      alongside services) — several salon deployments have asked for
      this.
- [ ] Waitlist support for fully-booked time slots.
- [ ] Recurring appointment series (weekly PT sessions, monthly
      maintenance, etc.) rather than booking each occurrence separately.

### Longer-term (platform direction)

- [ ] Move PII encryption keys to a real KMS (see KEY_MANAGEMENT.md) once
      there's a second deployment/tenant — the current model doesn't
      scale to multi-tenant SaaS.
- [ ] Formal SOC 2 / ISO 27001 readiness pass once the business has a
      customer base that requires it — this spec, SBOM.md, and
      KEY_MANAGEMENT.md are the technical inputs; the rest (risk
      register, incident response plan, vendor management, staff
      training) is organizational work outside this codebase.
- [ ] A proper multi-tenant architecture if BooKi is offered as
      a hosted product to more than one customer (today's deployment
      model is one instance per customer, which is simpler but doesn't
      scale operationally past a handful of clients).
- [ ] Native mobile app for providers (check-in/out, today's schedule,
      commission view) — most requested by staff, not management, in
      informal feedback so far.
- [ ] Public API (documented, token-authenticated) for third-party
      integrations beyond the existing Google Calendar/CalDAV/webhook
      hooks.

---

## 12. Where things live

- Application source: this repository
  (`github.com/miracmk/ki-reservation`).
- Deployment (Docker Compose, `.env`, per-customer overrides): kept in
  each deploying project's own infrastructure repo/directory — **not**
  in this repository, since it contains real secrets.
- Marketing/showcase page: `https://software.kibusiness.co/ki-reservation.html`.
