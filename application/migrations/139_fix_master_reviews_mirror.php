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
 * BooKi (2026-09-16) - Fix the master DB `reviews` mirror schema.
 *
 * Migration 132 created the tenant-side `reviews` table (the appointment/token lifecycle
 * source of truth) on BOTH tenant and master DBs. But the master row is a different beast:
 * it is the marketplace-facing mirror written by Review_service::mirror_to_master() and read
 * by the Marketplace / Landing controllers. That consumer contract needs:
 *
 *   - `id_tenants`            the tenant the review belongs to (subquery + moderation queue)
 *   - `source_appointment_hash` the tenant's single-use token (unique - one master row per
 *                             tenant review request)
 *
 * while the tenant-only columns (`appointment_id`, `id_users_customer`, `token`) are NOT
 * populated on the master mirror and therefore must be nullable there.
 *
 * This migration detects the master DB (the `tenants` table only exists in the master) and
 * realigns only that copy. It is a no-op on tenant DBs, where 132's schema is correct.
 */
class Migration_Fix_master_reviews_mirror extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('reviews')) {
            return;
        }

        // The `tenants` table exists only in the master DB - tenant DBs never have it.
        $is_master = $this->db->table_exists('tenants');

        if (!$is_master) {
            return;
        }

        // Tenant-only NOT NULL columns are never written by the mirror - allow NULL.
        foreach (['appointment_id', 'id_users_customer', 'token'] as $col) {
            if ($this->db->field_exists($col, 'reviews')) {
                $this->dbforge->modify_column('reviews', [
                    $col => [
                        'type' => $col === 'token' ? 'VARCHAR' : 'INT',
                        'constraint' => $col === 'token' ? 64 : null,
                        'unsigned' => $col !== 'token',
                        'null' => true,
                    ],
                ]);
            }
        }

        if (!$this->db->field_exists('id_tenants', 'reviews')) {
            $this->dbforge->add_column('reviews', [
                'id_tenants' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
            ]);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('reviews') .
                ' ADD INDEX idx_reviews_id_tenants (id_tenants)'
            );
        }

        if (!$this->db->field_exists('source_appointment_hash', 'reviews')) {
            $this->dbforge->add_column('reviews', [
                'source_appointment_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
            ]);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('reviews') .
                ' ADD UNIQUE KEY uq_reviews_source_hash (source_appointment_hash)'
            );
        }
    }

    public function down(): void
    {
        if (!$this->db->table_exists('reviews') || !$this->db->table_exists('tenants')) {
            return;
        }

        foreach (['source_appointment_hash', 'id_tenants'] as $col) {
            if ($this->db->field_exists($col, 'reviews')) {
                $this->dbforge->drop_column('reviews', $col);
            }
        }
    }
}