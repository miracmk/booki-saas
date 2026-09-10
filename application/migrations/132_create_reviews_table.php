<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Reviews (Dalga 3 / Faz 3.4, 2026-09-09).
 *
 * Tenant-side review request track (the source of truth for the review flow):
 *
 *   reviews  – one row per review request issued by the automation engine.
 *              Each request carries a single-use `token` that IS the
 *              source_appointment_hash written to the master DB's `reviews`
 *              table when the tenant publishes a review.
 *
 * Status lifecycle:
 *   requested -> pending (customer submitted via token link / marketplace form)
 *            -> published OR rejected (tenant admin moderation)
 *
 * Also adds:
 *   - `reviews_enabled` setting (default 1)
 *   - `reviews` column on ea_roles (bitmask: 1=view 2=add 4=edit 8=del 15=all)
 *   - converts the seeded "Değerlendirme isteği" automation rule from a plain
 *     message action to the `review_request` action (token link messages).
 * -------------------------------------------------------------------------- */

class Migration_Create_reviews_table extends CI_Migration
{
    public function up(): void
    {
        // ── reviews (tenant side) ───────────────────────────────────────
        if (!$this->db->table_exists('reviews')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'appointment_id' => ['type' => 'INT', 'unsigned' => TRUE],
                'id_users_customer' => ['type' => 'INT', 'unsigned' => TRUE],
                'token' => ['type' => 'VARCHAR', 'constraint' => 64],
                'customer_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => TRUE],
                'customer_phone_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => TRUE],
                'rating' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => TRUE],
                'comment' => ['type' => 'TEXT', 'null' => TRUE],
                'status' => ['type' => "ENUM('requested','pending','published','rejected')", 'default' => 'requested'],
                'created_at' => ['type' => 'DATETIME'],
                'submitted_at' => ['type' => 'DATETIME', 'null' => TRUE],
                'moderated_at' => ['type' => 'DATETIME', 'null' => TRUE],
                'moderated_by' => ['type' => 'INT', 'null' => TRUE],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('appointment_id');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('reviews', TRUE);

            // UNIQUE token - dbforge has no add_key(unique) helper; single-use
            // review links must be unique so the master mirror can key on them.
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('reviews') .
                ' ADD UNIQUE KEY uq_reviews_token (token)'
            );
        }

        // ── reviews_enabled setting ─────────────────────────────────────
        if (!$this->db->get_where('settings', ['name' => 'reviews_enabled'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'reviews_enabled',
                'value' => '1',
            ]);
        }

        // ── ea_roles: reviews bitmask ───────────────────────────────────
        if ($this->db->field_exists('marketing', 'roles') && !$this->db->field_exists('reviews', 'roles')) {
            $this->dbforge->add_column('roles', [
                'reviews' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0, 'after' => 'marketing'],
            ]);
        } elseif (!$this->db->field_exists('reviews', 'roles')) {
            $this->dbforge->add_column('roles', [
                'reviews' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0, 'after' => 'is_admin'],
            ]);
        }

        // Seed permissions (bitmask: 15=all) - admin gets full access.
        $this->db->update('roles', ['reviews' => '15'], ['slug' => 'admin']);

        // ── Up-convert the seeded "Değerlendirme isteği" rule ───────────
        // Only if it still carries the original seeded plain `message` action -
        // never clobber a rule the operator already edited. The seeded text is
        // stored JSON-escaped (\u011f for 'ğ'), so match on the decoded action
        // text rather than the raw (escaped) actions JSON.
        $seeded = $this->db
            ->get_where('automation_rules', [
                'name' => 'Değerlendirme isteği (2 saat sonra sms)',
                'event' => 'appointment_completed',
            ])
            ->row_array();

        $is_seeded_message = false;

        if ($seeded) {
            $actions = json_decode((string) $seeded['actions'], true);
            if (is_array($actions)) {
                foreach ($actions as $action) {
                    if (($action['type'] ?? '') === 'message'
                        && str_contains((string) ($action['text'] ?? ''), 'değerlendir')) {
                        $is_seeded_message = true;
                        break;
                    }
                }
            }
        }

        if ($seeded && $is_seeded_message) {
            $this->db->update('automation_rules', [
                'actions' => json_encode([
                    ['type' => 'review_request', 'recipient' => 'customer',
                     'channels' => 'sms,whatsapp', 'subject' => '',
                     'text' => 'Merhaba {customer_name}, {service_name} deneyiminizi değerlendirmeniz bizi çok mutlu eder: {review_link} - {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $seeded['id']]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('reviews')) {
            $this->dbforge->drop_table('reviews');
        }

        if ($this->db->get_where('settings', ['name' => 'reviews_enabled'])->num_rows()) {
            $this->db->delete('settings', ['name' => 'reviews_enabled']);
        }

        if ($this->db->field_exists('reviews', 'roles')) {
            $this->dbforge->drop_column('roles', 'reviews');
        }

        // Restore the original promoted message action (best-effort).
        $rule = $this->db
            ->get_where('automation_rules', [
                'name' => 'Değerlendirme isteği (2 saat sonra sms)',
                'event' => 'appointment_completed',
            ])
            ->row_array();

        if ($rule && str_contains((string) $rule['actions'], 'review_request')) {
            $this->db->update('automation_rules', [
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms', 'subject' => '',
                     'text' => 'Merhaba {customer_name}, {service_name} deneyiminizi değerlendirir misiniz? {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $rule['id']]);
        }
    }
}