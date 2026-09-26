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
 * Accounting Settings model.
 *
 * Manages OAuth tokens and configuration for accounting system integrations.
 * Tokens are encrypted at rest using sf_pii_encrypt() and decrypted on retrieval.
 *
 * @package Models
 */
class Accounting_settings_model extends App_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'auto_invoice_enabled' => 'boolean',
    ];

    /**
     * Get the current accounting connection configuration (decrypted).
     *
     * Returns the latest accounting_connections record, with access_token and
     * refresh_token decrypted if they are encrypted. If no connection exists, returns null.
     *
     * @return array|null Connection record with decrypted tokens, or null if not configured.
     */
    public function get_connection(): ?array
    {
        $record = $this->db->select('*')
            ->from($this->db->dbprefix('accounting_connections'))
            ->where('provider', 'parasut')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$record) {
            return null;
        }

        // Decrypt tokens
        if (!empty($record['access_token'])) {
            $record['access_token'] = sf_pii_decrypt($record['access_token']);
        }

        if (!empty($record['refresh_token'])) {
            $record['refresh_token'] = sf_pii_decrypt($record['refresh_token']);
        }

        return $record;
    }

    /**
     * Save (insert or update) accounting connection configuration.
     *
     * Encrypts access_token and refresh_token before storage.
     * Updates the existing connection or inserts a new one if none exists.
     *
     * @param array $data Configuration data with keys: provider, access_token, refresh_token,
     *                     company_id, expires_at, auto_invoice_enabled.
     *
     * @throws RuntimeException If encryption fails.
     */
    public function save_connection(array $data): void
    {
        // Ensure provider is set
        if (empty($data['provider'])) {
            $data['provider'] = 'parasut';
        }

        // Encrypt tokens if present
        if (!empty($data['access_token'])) {
            $data['access_token'] = sf_pii_encrypt($data['access_token']);
        }

        if (!empty($data['refresh_token'])) {
            $data['refresh_token'] = sf_pii_encrypt($data['refresh_token']);
        }

        // Set timestamps
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Check if a connection already exists
        $existing = $this->db->select('id')
            ->from($this->db->dbprefix('accounting_connections'))
            ->where('provider', $data['provider'])
            ->limit(1)
            ->get()
            ->row();

        if ($existing) {
            // Update existing connection
            $this->db->where('id', $existing->id);
            $this->db->update($this->db->dbprefix('accounting_connections'), $data);
        } else {
            // Insert new connection
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert($this->db->dbprefix('accounting_connections'), $data);
        }
    }

    /**
     * Delete the accounting connection (e.g., on disconnection).
     *
     * @param string $provider Provider name (default: 'parasut').
     */
    public function delete_connection(string $provider = 'parasut'): void
    {
        $this->db->where('provider', $provider);
        $this->db->delete($this->db->dbprefix('accounting_connections'));
    }
}
