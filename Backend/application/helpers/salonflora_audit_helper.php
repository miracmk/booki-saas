<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - audit log helper (ISO 27001 / HIPAA-style access
 * accountability, 2026-08-24). One function, audit_log(), called from the
 * specific controller actions that matter for compliance review:
 * authentication (success/failure) and any create/update/delete/anonymize of
 * customer, provider, or payment data. See each call site's inline comment
 * for why that particular action was chosen. This deliberately does NOT log
 * every read - that would make the table enormous and the log unreadable -
 * it logs actions with real accountability value: who changed or erased
 * what, and who logged in or failed to.
 * ---------------------------------------------------------------------------- */

if (!function_exists('audit_log')) {
    /**
     * Record one audit log entry. Never throws - a logging failure must not
     * break the action being audited, so any DB error here is swallowed and
     * sent to the regular error log instead.
     *
     * @param string $action Dot-namespaced action, e.g. "customer.anonymize", "auth.login_failed".
     * @param string|null $entity_type e.g. "customer", "provider", "appointment".
     * @param int|null $entity_id
     * @param array $details Small, non-PII-bearing context (e.g. ['reason' => 'manual_erasure']).
     *   Never pass raw customer/provider PII here - the log itself must not become a second
     *   unencrypted copy of the data it's supposed to help protect.
     */
    function audit_log(string $action, ?string $entity_type = null, ?int $entity_id = null, array $details = []): void
    {
        try {
            $CI = &get_instance();

            $user_id = session('user_id');
            $role_slug = session('role_slug');

            $actor_label = null;

            if ($user_id) {
                $actor_label = trim((string) session('user_email'));
            }

            $CI->db->insert('audit_log', [
                'created_at' => date('Y-m-d H:i:s'),
                'action' => $action,
                'id_users' => $user_id ?: null,
                'actor_role' => $role_slug,
                'actor_label' => $actor_label !== '' ? $actor_label : null,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'ip_address' => $CI->input->ip_address(),
                'details' => empty($details) ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'audit_log() failed: ' . $e->getMessage());
        }
    }
}
