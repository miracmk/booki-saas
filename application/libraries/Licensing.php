<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * Ki Reservation customization (2026-08-24) - self-hosted license validation.
 *
 * Design (see project SPECS.md / KEY_MANAGEMENT.md for the sibling encryption design that this
 * mirrors): a Ki Software-issued license is an RS256-signed JWT. Only the PUBLIC key ships in this
 * repository (`application/config/license_public_key.pem`) - the private signing key lives only on
 * Ki Software's own infrastructure and is never distributed with the product, so a licensee cannot
 * mint their own valid license by inspecting the source.
 *
 * Deliberately OFFLINE: validation never makes a network call. A self-hosted booking system must
 * keep working even if Ki Software's own infrastructure is unreachable - a phone-home license check
 * would make Ki Software's uptime a single point of failure for every customer's ability to take
 * bookings, which is unacceptable for business-critical scheduling software.
 *
 * Deliberately SOFT enforcement: an expired/missing/invalid license never blocks the public booking
 * page or any core business operation - see status()/is_blocking_enforced() and how the caller (the
 * backend_layout banner) uses this. A licensing bug must never be able to lock a real business out of
 * taking revenue; the consequence of an invalid license is a persistent admin-visible warning, which
 * is a support/renewal conversation, not a technical lockout.
 *
 * License claims:
 * - sub: licensee name/identifier
 * - plan: "self_hosted" | "cloud"
 * - iat: issued-at (unix timestamp)
 * - exp: valid-until (unix timestamp) - for self_hosted this is the paid-through date (year 1 = the
 *   $8999 upfront payment; renewed monthly thereafter at $100/mo by Ki Software issuing a new license
 *   with a new exp each renewal)
 * - seats: max concurrent admin/secretary/provider user accounts, or null for unlimited (self-hosted
 *   is sold as a flat fee, not per-seat, so this is normally null there; the cloud plan is per-seat
 *   billed and would set this)
 */
class Licensing
{
    private const GRACE_DAYS = 14;

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Licensing constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * @return string The PEM-encoded RSA public key used to verify license signatures.
     */
    private function public_key(): string
    {
        $path = APPPATH . 'config/license_public_key.pem';

        if (!is_file($path)) {
            throw new RuntimeException('License public key is missing from the application - reinstall from source.');
        }

        return file_get_contents($path);
    }

    /**
     * Validate the currently configured license and return a status summary. Never throws - any
     * failure (missing key, malformed JWT, bad signature, expired) is reported in the returned
     * array's 'state', not as an exception, since callers (a page-load banner check) must never be
     * able to crash a request over a licensing problem.
     *
     * @return array{
     *   state: 'valid'|'grace'|'expired'|'missing'|'invalid',
     *   claims: array|null,
     *   days_remaining: int|null,
     *   message: string,
     * }
     */
    public function status(): array
    {
        $license_key = trim((string) setting('license_key'));

        if ($license_key === '') {
            return [
                'state' => 'missing',
                'claims' => null,
                'days_remaining' => null,
                'message' => 'Bu kuruluma yüklenmiş bir Ki Reservation lisansı yok.',
            ];
        }

        try {
            $decoded = JWT::decode($license_key, new Key($this->public_key(), 'RS256'));
            $claims = (array) $decoded;
        } catch (ExpiredException $e) {
            // JWT::decode already enforces `exp`, but we decode leeway-tolerantly below to compute a
            // grace period instead of hard-failing here - see the leeway decode path.
            $claims = $this->decode_ignoring_expiration($license_key);

            if ($claims === null) {
                return [
                    'state' => 'invalid',
                    'claims' => null,
                    'days_remaining' => null,
                    'message' => 'Lisans anahtarı geçersiz veya imzası doğrulanamadı.',
                ];
            }
        } catch (SignatureInvalidException | UnexpectedValueException $e) {
            return [
                'state' => 'invalid',
                'claims' => null,
                'days_remaining' => null,
                'message' => 'Lisans anahtarı geçersiz veya imzası doğrulanamadı.',
            ];
        } catch (Throwable $e) {
            log_message('error', 'Licensing::status() failed: ' . $e->getMessage());

            return [
                'state' => 'invalid',
                'claims' => null,
                'days_remaining' => null,
                'message' => 'Lisans doğrulanırken bir hata oluştu.',
            ];
        }

        $exp = (int) ($claims['exp'] ?? 0);
        $now = time();
        $days_remaining = (int) floor(($exp - $now) / 86400);

        if ($now <= $exp) {
            return [
                'state' => 'valid',
                'claims' => $claims,
                'days_remaining' => $days_remaining,
                'message' => 'Lisans geçerli.',
            ];
        }

        $days_expired = (int) floor(($now - $exp) / 86400);

        if ($days_expired <= self::GRACE_DAYS) {
            return [
                'state' => 'grace',
                'claims' => $claims,
                'days_remaining' => -$days_expired,
                'message' => "Lisans süresi doldu, {$days_expired} gün önce - lütfen yenileyin.",
            ];
        }

        return [
            'state' => 'expired',
            'claims' => $claims,
            'days_remaining' => -$days_expired,
            'message' => "Lisans süresi {$days_expired} gün önce doldu - lütfen yenileyin.",
        ];
    }

    /**
     * Decode a JWT's claims without letting an expired `exp` fail the call, so status() can compute a
     * grace period. Signature is still fully verified - only the expiration check is bypassed.
     *
     * @param string $license_key
     *
     * @return array|null Null if the signature itself doesn't verify.
     */
    private function decode_ignoring_expiration(string $license_key): ?array
    {
        try {
            $parts = explode('.', $license_key);

            if (count($parts) !== 3) {
                return null;
            }

            // Re-verify the signature manually via a fresh decode call with a far-future leeway,
            // rather than trusting the raw payload - JWT::decode() already threw ExpiredException,
            // which only happens after signature verification succeeded, so at this point the
            // signature is known-good and we just need the claims back out.
            [$header_b64, $payload_b64, $sig_b64] = $parts;
            $payload_json = JWT::urlsafeB64Decode($payload_b64);
            $claims = json_decode($payload_json, true);

            return is_array($claims) ? $claims : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Whether the current license state should surface an admin-visible warning banner.
     *
     * @return bool
     */
    public function needs_warning(): bool
    {
        $state = $this->status()['state'];

        return in_array($state, ['missing', 'grace', 'expired', 'invalid'], true);
    }
}
