<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stores per-customer notification channel preferences.
 */
class User_notification_preferences_model extends EA_Model
{
    private const CHANNELS = [
        'email',
        'sms',
        'call',
        'whatsapp',
        'telegram',
        'instagram',
    ];

    public function get(int $user_id): array
    {
        $row = $this->db->get_where('user_notification_preferences', ['id_users' => $user_id])->row_array();

        if (!$row) {
            return $this->defaults();
        }

        $row['id_users'] = (int) $row['id_users'];
        $row['mode'] = in_array($row['mode'], ['default', 'custom'], true) ? $row['mode'] : 'default';

        foreach (self::CHANNELS as $channel) {
            $row[$channel . '_enabled'] = (bool) ($row[$channel . '_enabled'] ?? false);
        }

        return $row;
    }

    public function save(int $user_id, array $preferences): array
    {
        $mode = ($preferences['mode'] ?? 'default') === 'custom' ? 'custom' : 'default';
        $data = [
            'id_users' => $user_id,
            'mode' => $mode,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        foreach (self::CHANNELS as $channel) {
            $data[$channel . '_enabled'] = !empty($preferences[$channel . '_enabled']) ? 1 : 0;
        }

        $existing = $this->db->get_where('user_notification_preferences', ['id_users' => $user_id])->row_array();

        if ($existing) {
            $this->db->update('user_notification_preferences', $data, ['id_users' => $user_id]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('user_notification_preferences', $data);
        }

        return $this->get($user_id);
    }

    public function delete(int $user_id): void
    {
        $this->db->delete('user_notification_preferences', ['id_users' => $user_id]);
    }

    public function channels_for(array $user, array $settings): array
    {
        $preferences = $this->get((int) ($user['id'] ?? 0));

        if ($preferences['mode'] !== 'custom') {
            $configured = $settings['default_notification_channels'] ?? $settings['default_notification_channel'] ?? 'telegram';
            return array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $configured)))));
        }

        $channels = [];
        foreach (self::CHANNELS as $channel) {
            if ($preferences[$channel . '_enabled']) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    public function channel_options(): array
    {
        return self::CHANNELS;
    }

    private function defaults(): array
    {
        $defaults = [
            'id_users' => null,
            'mode' => 'default',
        ];

        foreach (self::CHANNELS as $channel) {
            $defaults[$channel . '_enabled'] = false;
        }

        return $defaults;
    }
}
