<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation (Dalga 3 / Faz 3.4) - Review Service
 *
 * Shared logic between the public review flow and the tenant admin moderation:
 *
 *  - claim_for_submission()   atomically consumes a single-use token (tenant DB,
 *                             current $this->db must be the tenant DB).
 *  - mirror_to_master()       pushes a tenant review's status to the master DB's
 *                             `reviews` row, keyed by source_appointment_hash =
 *                             tenant token (the marketplace's published store).
 *  - claim_in_tenant()        used from the master-bound Marketplace controller to
 *                             validate/consume a token against the right tenant DB.
 *
 * The tenant `reviews` table is the source of truth for the request lifecycle;
 * the master `ea_reviews` row is the marketplace-facing mirror.
 * ---------------------------------------------------------------------------- */

class Review_service
{
    private EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Atomically consume a single-use token on the current (tenant) DB:
     * `requested` → `pending`, stamping submitted_at. Returns the review row
     * (with the customer's hashed phone intact) or null when the token is
     * unknown or already consumed.
     */
    public function claim_for_submission(string $token): ?array
    {
        if (!$this->CI->db->table_exists('reviews')) {
            return null;
        }

        $review = $this->CI->db->get_where('reviews', ['token' => $token])->row_array();

        if (!$review || $review['status'] !== Reviews_model::STATUS_REQUESTED) {
            return null;
        }

        $claimed = $this->CI->db
            ->where('token', $token)
            ->where('status', Reviews_model::STATUS_REQUESTED)
            ->set('status', Reviews_model::STATUS_PENDING)
            ->set('submitted_at', date('Y-m-d H:i:s'))
            ->update('reviews');

        if (!$claimed || $this->CI->db->affected_rows() !== 1) {
            return null;
        }

        $review['status'] = Reviews_model::STATUS_PENDING;
        $review['submitted_at'] = date('Y-m-d H:i:s');

        return $review;
    }

    /**
     * Mirror a tenant review's moderation status to the master DB's `reviews`
     * row (source_appointment_hash = tenant token). Call from a tenant context.
     *
     *  - published → insert/update the master row as published
     *  - rejected  → delete the master row (removed from marketplace)
     *  - pending   → insert a pending master row for the moderation queue
     *
     * Never touches the tenant price/PII data - only customer_name, phone hash,
     * rating, comment, timestamps.
     */
    public function mirror_to_master(array $review, string $status): void
    {
        $tenant = tenant_context();

        if (!$tenant || !isset($tenant['id']) || empty($review['token']) || !$review['rating']) {
            return;
        }

        $master = $this->CI->load->database('default', true);

        if (!$master->table_exists('reviews')) {
            return;
        }

        if ($status === Reviews_model::STATUS_REJECTED) {
            $master->delete('reviews', ['source_appointment_hash' => $review['token']]);

            return;
        }

        $row = $master->get_where('reviews', ['source_appointment_hash' => $review['token']])->row_array();

        $data = [
            'id_tenants' => (int) $tenant['id'],
            'customer_name' => (string) ($review['customer_name'] ?? 'Anonymous'),
            'customer_phone_hash' => $review['customer_phone_hash'] ?? null,
            'rating' => (int) $review['rating'],
            'comment' => !empty($review['comment']) ? $review['comment'] : null,
            'source_appointment_hash' => $review['token'],
            'status' => $status,
        ];

        if ($row) {
            $master->update('reviews', $data, ['id' => $row['id']]);

            return;
        }

        $data['created_at'] = !empty($review['submitted_at']) ? $review['submitted_at'] : date('Y-m-d H:i:s');

        $master->insert('reviews', $data);
    }

    /**
     * Validate + consume a review token against a SPECIFIC tenant's DB (called
     * from the master-bound Marketplace controller, where $this->db is the master
     * DB, not the tenant's). Opens a throwaway connection to the tenant's DB, so
     * calling this never disturbs the current request's connection.
     *
     * @param array $tenant A `tenants` row (db_host/db_username/db_password/
     *   pii_hash_key, all tenant_master_encrypt()-ed).
     *
     * @return array|null The claimed review row, or null on invalid/used token.
     */
    public function claim_in_tenant(array $tenant, string $token): ?array
    {
        $db = $this->CI->load->database([
            'hostname' => $tenant['db_host'],
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
        ], true);

        if (!$db->table_exists('reviews')) {
            return null;
        }

        $review = $db->get_where('reviews', ['token' => $token])->row_array();

        if (!$review || $review['status'] !== Reviews_model::STATUS_REQUESTED) {
            return null;
        }

        $claimed = $db
            ->where('token', $token)
            ->where('status', Reviews_model::STATUS_REQUESTED)
            ->set('status', Reviews_model::STATUS_PENDING)
            ->set('submitted_at', date('Y-m-d H:i:s'))
            ->update('reviews');

        if (!$claimed || $db->affected_rows() !== 1) {
            return null;
        }

        $review['status'] = Reviews_model::STATUS_PENDING;

        return $review;
    }
}