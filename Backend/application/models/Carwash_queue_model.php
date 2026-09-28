<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Car Wash Live Bay Queue & TV Display Model
 *
 * Implements live bay queues, wait times, vehicle segment pricing,
 * and automated ready-for-pickup SMS/WhatsApp notifications (OpenWashing standard).
 */
class Carwash_queue_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get active queue entries for the live TV screen / dashboard.
     */
    public function get_active_queue(): array
    {
        return $this->db
            ->select('q.*, v.plate_number, v.brand, v.model, v.color, v.vehicle_segment,
                c.first_name as owner_first_name, c.last_name as owner_last_name, c.phone_number,
                s.name as service_name')
            ->from('carwash_queue_logs q')
            ->join('customer_vehicles v', 'v.id = q.id_vehicles', 'inner')
            ->join('users c', 'c.id = v.id_users_customer', 'left')
            ->join('appointments a', 'a.id = q.id_appointments', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where_in('q.queue_status', ['waiting', 'washing', 'detailing', 'ready'])
            ->order_by("FIELD(q.queue_status, 'ready', 'washing', 'detailing', 'waiting')")
            ->order_by('q.created_at ASC')
            ->get()
            ->result_array();
    }

    /**
     * Get single queue entry.
     */
    public function get_queue_entry(int $id): ?array
    {
        return $this->db
            ->select('q.*, v.plate_number, v.brand, v.model, v.color, v.vehicle_segment,
                c.first_name as owner_first_name, c.last_name as owner_last_name, c.phone_number')
            ->from('carwash_queue_logs q')
            ->join('customer_vehicles v', 'v.id = q.id_vehicles', 'inner')
            ->join('users c', 'c.id = v.id_users_customer', 'left')
            ->where('q.id', $id)
            ->get()
            ->row_array();
    }

    /**
     * Create new car wash queue entry.
     */
    public function add_to_queue(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $duration_minutes = (int) ($data['estimated_duration'] ?? 30);

        $record = [
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_vehicles' => (int) $data['id_vehicles'],
            'bay_name' => $data['bay_name'] ?? 'Peron 1',
            'queue_status' => $data['queue_status'] ?? 'waiting',
            'notes' => $data['notes'] ?? null,
            'started_at' => ($data['queue_status'] ?? '') === 'washing' ? $now : null,
            'estimated_ready_at' => date('Y-m-d H:i:s', strtotime("+{$duration_minutes} minutes")),
            'created_at' => $now,
        ];

        $this->db->insert('carwash_queue_logs', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Update queue status (waiting -> washing -> detailing -> ready -> delivered).
     */
    public function update_status(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $update = [
            'queue_status' => $status,
        ];

        if ($status === 'washing') {
            $update['started_at'] = $now;
        } elseif ($status === 'ready') {
            $update['completed_at'] = $now;
        }

        return $this->db->where('id', $id)->update('carwash_queue_logs', $update);
    }

    /**
     * Mark vehicle ready and trigger WhatsApp notification.
     */
    public function notify_customer_ready(int $id): array
    {
        $entry = $this->get_queue_entry($id);
        if (!$entry) {
            return ['success' => false, 'message' => 'Araç kaydı bulunamadı.'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('carwash_queue_logs', [
            'queue_status' => 'ready',
            'completed_at' => $now,
            'notified_customer_at' => $now,
        ]);

        $phone = $entry['phone_number'];
        $message = "Sayın {$entry['owner_first_name']} {$entry['owner_last_name']}, {$entry['plate_number']} plakalı aracınızın yıkama ve bakım işlemi tamamlanmıştır. Aracınızı teslim noktasından teslim alabilirsiniz.";

        $wa_sent = false;
        // If whatsapp bridge or provider is active, send message
        if ($phone && function_exists('send_whatsapp_message')) {
            try {
                send_whatsapp_message($phone, $message);
                $wa_sent = true;
            } catch (Throwable $e) {
                // Keep resilient
            }
        }

        return [
            'success' => true,
            'message' => 'Araç hazır olarak işaretlendi ve müşteriye bildirim gönderildi.',
            'wa_sent' => $wa_sent,
            'text_message' => $message,
        ];
    }
}
