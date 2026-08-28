<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - WhatsApp messages log model (2026-08-27).
 *
 * Manages the whatsapp_messages table - inbound/outbound message history.
 * Mirrors the pattern of telegram_messages.
 *
 * @package Models
 */

class Whatsapp_messages_model extends EA_Model
{
    /**
     * Save a new WhatsApp message to the log.
     *
     * @param array $message Array with keys: id_users, wa_id, direction, message, status, template_name
     *
     * @return int The inserted row's id.
     */
    public function save(array $message): int
    {
        $insert = [
            'id_users' => $message['id_users'] ?? null,
            'wa_id' => $message['wa_id'],
            'direction' => $message['direction'] ?? 'in',
            'message' => $message['message'],
            'status' => $message['status'] ?? null,
            'template_name' => $message['template_name'] ?? null,
            'created_at' => $message['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        $this->db->insert('whatsapp_messages', $insert);

        return $this->db->insert_id();
    }

    /**
     * Get a message by its WhatsApp ID.
     *
     * @param string $wa_id WhatsApp message ID
     *
     * @return array|null The message row, or null if not found.
     */
    public function get_by_wa_id(string $wa_id): ?array
    {
        return $this->db->get_where('whatsapp_messages', ['wa_id' => $wa_id])->row_array() ?: null;
    }

    /**
     * Get recent messages (for staff panel display).
     *
     * @param int $limit Number of messages to fetch (default 50)
     *
     * @return array Array of message rows, joined with user first/last names for context.
     */
    public function get_recent(int $limit = 50): array
    {
        return $this->db
            ->select('whatsapp_messages.*, users.first_name, users.last_name')
            ->from('whatsapp_messages')
            ->join('users', 'users.id = whatsapp_messages.id_users', 'left')
            ->order_by('whatsapp_messages.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Faz 30 (KVKK export) - a customer's WhatsApp history. Matched on id_users OR wa_id, because
     * messages received before the customer record was linked carry only wa_id (id_users is NULL on
     * that table by design - see migration 105).
     *
     * @param int $customer_id
     * @param string|null $wa_id Digits-only phone (E.164 without '+'), or null to match on FK only.
     * @return array
     */
    public function get_for_customer(int $customer_id, ?string $wa_id = null): array
    {
        $this->db->group_start()->where('id_users', $customer_id);

        if (!empty($wa_id)) {
            $this->db->or_group_start()
                ->where('wa_id', $wa_id)
                ->or_where('wa_id', '+' . $wa_id)
                ->group_end();
        }

        $this->db->group_end();

        return $this->db->order_by('created_at', 'ASC')->get('whatsapp_messages')->result_array();
    }
}
