<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - PII encryption backfill (KVKK hardening, 2026-08-24). Encrypts
 * email/phone_number/address/state/zip_code/notes IN PLACE for every existing `users` row (customers,
 * providers, admins, secretaries alike - all share this table) and populates the email_hash/
 * phone_number_hash exact-match search index. first_name/last_name are deliberately left in plaintext
 * (see salonflora_crypto_helper.php docblock - partial name search is a daily staff workflow that
 * encryption can't preserve without a much larger blind-index project).
 *
 * MUST ship in the same deploy as the application code that knows to decrypt these columns
 * (Customers_model, Providers_model, and every other read path - see project notes for the full
 * list). Running this migration alone, without that code, would make every customer/provider record
 * display raw ciphertext in the UI.
 *
 * Idempotent: sf_pii_is_encrypted() skips any value that's already tagged, so re-running this
 * migration (e.g. after a partial failure) is safe - already-encrypted rows are left untouched.
 *
 * Rollback: there is no automated down() - decrypting 3000+ rows back to plaintext programmatically
 * carries real risk of getting a partial/interrupted run wrong. Restore from the pre-migration
 * `mysqldump` full backup instead (see project notes for the backup file taken immediately before
 * this migration ran).
 * ---------------------------------------------------------------------------- */

class Migration_Pii_encryption_backfill extends App_Migration
{
    private const ENCRYPT_ONLY_COLUMNS = ['address', 'state', 'zip_code', 'notes'];

    private const ENCRYPT_AND_HASH_COLUMNS = ['email', 'phone_number'];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!function_exists('sf_pii_encrypt')) {
            require_once APPPATH . 'helpers/salonflora_crypto_helper.php';
        }

        $rows = $this->db
            ->select(
                'id, ' .
                    implode(', ', self::ENCRYPT_ONLY_COLUMNS) .
                    ', ' .
                    implode(', ', self::ENCRYPT_AND_HASH_COLUMNS),
            )
            ->from('users')
            ->get()
            ->result_array();

        $encrypted_count = 0;
        $skipped_count = 0;

        foreach ($rows as $row) {
            $update = [];

            foreach (self::ENCRYPT_ONLY_COLUMNS as $column) {
                $value = $row[$column];

                if ($value === null || $value === '' || sf_pii_is_encrypted($value)) {
                    continue;
                }

                $update[$column] = sf_pii_encrypt($value);
            }

            foreach (self::ENCRYPT_AND_HASH_COLUMNS as $column) {
                $value = $row[$column];

                if ($value === null || $value === '' || sf_pii_is_encrypted($value)) {
                    continue;
                }

                $update[$column] = sf_pii_encrypt($value);
                $update[$column . '_hash'] = sf_pii_hash($value);
            }

            if (empty($update)) {
                $skipped_count++;

                continue;
            }

            $this->db->where('id', $row['id'])->update('users', $update);

            $encrypted_count++;
        }

        log_message(
            'info',
            "Salon Flora PII backfill: encrypted {$encrypted_count} rows, skipped {$skipped_count} (already encrypted or empty).",
        );
    }

    /**
     * Downgrade method.
     *
     * Intentionally not implemented - see the class docblock.
     */
    public function down(): void {}
}
