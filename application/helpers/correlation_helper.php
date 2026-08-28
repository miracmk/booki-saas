<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Correlation ID helper (Faz 32+33, 2026-08-28).
 *
 * Generates or retrieves a request correlation ID for distributed tracing.
 * Reads from X-Request-ID header if present, falls back to random generation.
 * ---------------------------------------------------------------------------- */

if (!function_exists('correlation_id')) {
    /**
     * Get or generate the current request's correlation ID for distributed tracing.
     *
     * On first call, checks for an X-Request-ID header (UUID format), falls back
     * to random generation, then caches the result statically to ensure the same
     * ID is returned for all subsequent calls in this request.
     *
     * @return string A 32-character lowercase hex string.
     */
    function correlation_id(): string
    {
        static $id = null;

        if ($id === null) {
            $id = isset($_SERVER['HTTP_X_REQUEST_ID']) && preg_match('/^[a-f0-9]{32}$/', $_SERVER['HTTP_X_REQUEST_ID'])
                ? $_SERVER['HTTP_X_REQUEST_ID']
                : bin2hex(random_bytes(16));
        }

        return $id;
    }
}
