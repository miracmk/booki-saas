<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Add security headers to protect against common attacks.
 *
 * This hook adds various security headers to HTTP responses to help protect
 * against XSS, clickjacking, MIME sniffing, and other attacks.
 */
function add_security_headers(): void
{
    // Prevent XSS attacks - allow inline scripts and styles for the app's functionality
    // But restrict to same-origin for other resources
    header('X-Content-Type-Options: nosniff');

    // Prevent clickjacking attacks
    header('X-Frame-Options: SAMEORIGIN');

    // Enable XSS filter in browsers (legacy, but still useful)
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy for privacy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy - restrict sensitive features, but allow mic/camera
    // on the document itself (self). Previous value `microphone=(), camera=()`
    // explicitly disallowed getUserMedia() on EVERY page (incl. the
    // superadmin voice-call modal) and Chrome logs:
    // "Permission 'microphone' is explicitly disallowed by permissions policy".
    // geolocation stays fully blocked; mic/camera are delegated to same-origin
    // only so no third-party iframe gets them for free.
    header('Permissions-Policy: geolocation=(), microphone=(self), camera=(self)');

    // BooKi customization (2026-08-24, ISO 27001/SOC 2 hardening) - Content-Security-Policy.
    // Every asset (JS/CSS/fonts/images) is self-hosted (see SBOM.md) - nothing here is loaded from a
    // third-party CDN - so a same-origin-only policy costs nothing functionally. 'unsafe-inline' is
    // still required for both script-src and style-src: the app relies on inline <script> blocks (the
    // vars()/script_vars() bootstrap data, see config_helper.php) and inline style attributes
    // throughout the views; removing that would require a broader refactor than this hardening pass.
    // frame-src 'self' is required for the email template editor's iframe (designMode WYSIWYG, see
    // email_template_settings.js) and the booking-confirmation preview iframe.
    header(
        "Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "img-src 'self' data:; " .
            "font-src 'self' data: https://fonts.gstatic.com; " .
            // NOTE (2026-09-21, superadmin voice-call fix): `connect-src 'self'`
            // alone breaks future direct-browser AI/SIP media (ElevenLabs, Gemini,
            // Zadarma) and `media-src` absence blocks blob: playback of TTS /
            // recorded audio. getUserMedia() itself needs no CSP, but once the mic
            // IS granted the call modal needs these. Keep 'self' first, allow-list
            // only the voice-AI/SIP origins actually used.
            "connect-src 'self' https://api.elevenlabs.io https://*.elevenlabs.io " .
            "https://generativelanguage.googleapis.com https://*.googleapis.com " .
            "https://api.zadarma.com https://*.zadarma.com wss: ws: blob:; " .
            "media-src 'self' blob: data:; " .
            "frame-src 'self'; " .
            "frame-ancestors 'self'; " .
            "form-action 'self'; " .
            "base-uri 'self'; " .
            "object-src 'none'",
    );

    // If the app is served over HTTPS, add HSTS header
    if (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        // Strict Transport Security - enforce HTTPS for 1 year
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
