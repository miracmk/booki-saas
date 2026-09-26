<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - schema for column-level PII encryption (KVKK hardening, 2026-08-24).
 * Pure schema change, no data is transformed here (see migration 089 for the actual backfill) - this
 * migration alone does not change any application behavior, so it's safe to deploy on its own first.
 *
 * 1. Widen email/phone_number/address/state/zip_code to TEXT. Their old VARCHAR sizes were sized for
 *    plaintext; base64-encoded AES-256-GCM ciphertext (nonce + tag + ciphertext, plus the "SFENC1:"
 *    version tag - see salonflora_crypto_helper.php) runs meaningfully longer than the original
 *    plaintext (e.g. a 512-char VARCHAR email could produce ~730 bytes of ciphertext) and would risk
 *    hitting the strict-mode "Data too long" error on write otherwise.
 * 2. Add email_hash/phone_number_hash (CHAR(64), indexed) - an HMAC-SHA256 exact-match search index,
 *    since a LIKE query can no longer run against encrypted ciphertext. Only these two fields get a
 *    search index (per the agreed scope) - address/state/zip_code/notes lose search entirely, which is
 *    an accepted tradeoff (see project notes) since they weren't the primary way staff look up a
 *    customer.
 * ---------------------------------------------------------------------------- */

class Migration_Pii_encryption_schema extends App_Migration
{
    private const WIDENED_COLUMNS = ['email', 'phone_number', 'address', 'state', 'zip_code'];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        foreach (self::WIDENED_COLUMNS as $column) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' MODIFY `' . $column . '` TEXT NULL');
        }

        if (!$this->db->field_exists('email_hash', 'users')) {
            $this->dbforge->add_column('users', [
                'email_hash' => [
                    'type' => 'CHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'email',
                ],
            ]);
        }

        if (!$this->db->field_exists('phone_number_hash', 'users')) {
            $this->dbforge->add_column('users', [
                'phone_number_hash' => [
                    'type' => 'CHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'phone_number',
                ],
            ]);
        }

        $existing_indexes = array_column(
            $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users'))->result_array(),
            'Key_name',
        );

        if (!in_array('idx_email_hash', $existing_indexes, true)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD INDEX idx_email_hash (email_hash)',
            );
        }

        if (!in_array('idx_phone_number_hash', $existing_indexes, true)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD INDEX idx_phone_number_hash (phone_number_hash)',
            );
        }
    }

    /**
     * Downgrade method.
     *
     * Does not narrow the widened columns back (that would be a lossy/risky operation to run
     * automatically, and there's no functional reason to - a TEXT column happily holds a short
     * plaintext string too). Drops only the additive hash columns/indexes.
     */
    public function down(): void
    {
        $existing_indexes = array_column(
            $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users'))->result_array(),
            'Key_name',
        );

        if (in_array('idx_phone_number_hash', $existing_indexes, true)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_phone_number_hash');
        }

        if (in_array('idx_email_hash', $existing_indexes, true)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_email_hash');
        }

        if ($this->db->field_exists('phone_number_hash', 'users')) {
            $this->dbforge->drop_column('users', 'phone_number_hash');
        }

        if ($this->db->field_exists('email_hash', 'users')) {
            $this->dbforge->drop_column('users', 'email_hash');
        }
    }
}
