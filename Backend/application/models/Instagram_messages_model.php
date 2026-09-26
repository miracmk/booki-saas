<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Instagram messages log model.
 *
 * Manages the instagram_messages table - inbound/outbound message history.
 * Mirrors Whatsapp_messages_model and telegram_messages patterns.
 *
 * @package Models
 */

class Instagram_messages_model extends App_Model
{
    /**
     * Save a new Instagram message to the log.
     *
     * @param array $message Array with keys: id_users, instagram_user_id, direction, message, status
     *
     * @return int The inserted row's id.
     */
    public function save(array $message): int
    {
        $insert = [
            'id_users' => $message['id_users'] ?? null,
            'instagram_user_id' => $message['instagram_user_id'],
            'direction' => $message['direction'] ?? 'in',
            'message' => $message['message'],
            'status' => $message['status'] ?? null,
            'created_at' => $message['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $message['updated_at'] ?? null,
        ];

        $this->db->insert('instagram_messages', $insert);

        return $this->db->insert_id();
    }

    /**
     * Get recent messages (for staff panel display).
     *
     * @param int $limit Number of messages to fetch (default 50)
     *
     * @return array Array of message rows, joined with user first/last names.
     */
    public function get_recent(int $limit = 50): array
    {
        return $this->db
            ->select('instagram_messages.*, users.first_name, users.last_name')
            ->from('instagram_messages')
            ->join('users', 'users.id = instagram_messages.id_users', 'left')
            ->order_by('instagram_messages.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get conversation with a specific Instagram user.
     *
     * @param string $instagram_user_id
     * @param int $limit
     *
     * @return array
     */
    public function get_conversation(string $instagram_user_id, int $limit = 50): array
    {
        return $this->db
            ->select('instagram_messages.*, users.first_name, users.last_name')
            ->from('instagram_messages')
            ->join('users', 'users.id = instagram_messages.id_users', 'left')
            ->where('instagram_messages.instagram_user_id', $instagram_user_id)
            ->order_by('instagram_messages.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }
}
