# Key Management — Ki Reservation

This document covers how PII encryption keys are stored, and the
procedure for rotating them. See
`application/helpers/salonflora_crypto_helper.php` for the implementation.

## Current state (as deployed)

- Two independent 32-byte keys (`SF_PII_ENC_KEY` for AES-256-GCM
  encryption, `SF_PII_HASH_KEY` for the HMAC-SHA256 exact-match search
  index), base64-encoded, injected via environment variables
  (`docker-compose.yml`) from a root-only-readable `.env` file on the host.
- This is **not** a dedicated KMS/HSM. It is appropriate for a
  single-server deployment where the host itself is the trust boundary
  (same model as the database password, SMTP password, etc.). It is
  **not** appropriate as-is for a multi-tenant SaaS deployment with
  customer-specific keys, or for a compliance regime that specifically
  requires HSM-backed key custody (e.g. some HIPAA Business Associate
  Agreements, PCI DSS) — see "Upgrading to a real KMS" below.
- Ciphertext is tagged `SFENC1:` — a version prefix. This exists
  specifically so a future key/scheme change (`SFENC2:`, etc.) can
  coexist with old data during a rolling re-encryption, without any
  ambiguity about which key decrypts which row.

## Threat model this protects against

- **Database-only compromise** (e.g. a leaked mysqldump, a misconfigured
  backup, a read-only SQL injection): the attacker gets ciphertext, not
  plaintext, for phone/email/address/notes fields.
- **Does NOT protect against**: full application-server compromise (the
  keys live next to the app that uses them, by design, for a
  single-server deployment) or a malicious/compromised admin account
  (the application itself can always decrypt — this is data-at-rest
  protection, not access control; role-based visibility and the audit
  log are the controls for that).

## Key rotation procedure

Ki Reservation does not yet have automated key rotation tooling. To
rotate `SF_PII_ENC_KEY` and/or `SF_PII_HASH_KEY` manually:

1. **Generate new keys:**
   ```bash
   openssl rand -base64 32   # new SF_PII_ENC_KEY
   openssl rand -base64 32   # new SF_PII_HASH_KEY
   ```
2. **Back up the database** (full `mysqldump`) before starting — this is
   a bulk UPDATE across the `users` table and must be reversible if
   something goes wrong.
3. **Add the new keys under new environment variable names**
   (e.g. `SF_PII_ENC_KEY_V2`) alongside the existing ones — do not remove
   the old keys yet, decryption of existing rows still needs them.
4. **Write a one-off migration** that, for every row with an `SFENC1:`
   value: decrypts with the old key, re-encrypts with the new key using a
   new `sf_pii_encrypt_v2()` variant (producing an `SFENC2:`-tagged
   value), and re-computes the hash column with the new hash key. Process
   in batches; log progress; make it resumable (skip rows already
   `SFENC2:`-tagged, mirroring how migration 089's `sf_pii_is_encrypted()`
   check made the original backfill safely re-runnable).
5. **Verify** with a decrypt-and-compare pass against the pre-rotation
   backup for every row (same technique used to verify the original
   089/090 backfill — see project history) before trusting the rotation.
6. **Only after verification succeeds**, remove the old key environment
   variables and rename `SF_PII_ENC_KEY_V2` → `SF_PII_ENC_KEY` (and the
   hash key equivalent) in the next deploy.

## When to rotate

- On suspicion of key exposure (e.g. `.env` file leaked, a backup
  containing it was sent somewhere it shouldn't have been).
- On a routine schedule if required by a specific compliance framework
  the business is pursuing (ISO 27001 Annex A 8.24 asks for a documented
  cryptographic key management policy, including a rotation schedule —
  this document is that policy's technical half; the schedule itself is
  a business decision, not a technical one).
- When an employee/contractor with `.env` access (i.e. root SSH access to
  the host) leaves.

## Upgrading to a real KMS

For a future multi-tenant or higher-assurance deployment, the natural
upgrade path is to move `sf_pii_enc_key()`/`sf_pii_hash_key()` in
`salonflora_crypto_helper.php` from reading a static environment variable
to calling a cloud KMS (AWS KMS, GCP Cloud KMS, HashiCorp Vault, etc.) to
unwrap a data-encryption key on each request (envelope encryption). The
`SFENC1:`/`SFENC2:` versioning scheme already in place is designed to
make that transition — and any future one — a rotation, not a rewrite.
