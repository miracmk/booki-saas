<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Digital Waivers & Booking Addons Model
 * (FareHarbor / Bókun / Escape Room & Activity style)
 * ---------------------------------------------------------------------------- */

class Digital_waivers_model extends App_Model
{
    /**
     * Create or update digital waiver / contract template.
     */
    public function save_waiver(array $data): int
    {
        if (empty($data['title']) || empty($data['content_html'])) {
            throw new InvalidArgumentException('Feragatname başlığı ve içeriği zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'title' => trim($data['title']),
            'content_html' => $data['content_html'],
            'is_mandatory' => isset($data['is_mandatory']) ? (int) $data['is_mandatory'] : 1,
            'applicable_service_ids' => !empty($data['applicable_service_ids']) ? (is_array($data['applicable_service_ids']) ? implode(',', $data['applicable_service_ids']) : (string) $data['applicable_service_ids']) : (!empty($data['service_ids']) ? (is_array($data['service_ids']) ? implode(',', $data['service_ids']) : (string) $data['service_ids']) : null),
            'updated_at' => $now,
        ];

        if (empty($data['id'])) {
            $record['created_at'] = $now;
            $this->db->insert('digital_waivers', $record);
            return $this->db->insert_id();
        } else {
            $this->db->update('digital_waivers', $record, ['id' => (int) $data['id']]);
            return (int) $data['id'];
        }
    }

    /**
     * Record a digital signature on a waiver for a booking.
     */
    public function sign_waiver(array $data): int
    {
        if (empty($data['id_waivers']) || empty($data['signer_full_name']) || empty($data['signature_data'])) {
            throw new InvalidArgumentException('Feragatname, imzalayan adı ve imza verisi zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $sig = [
            'id_waivers' => (int) $data['id_waivers'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_users_customer' => !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : null,
            'signer_full_name' => trim($data['signer_full_name']),
            'signer_email' => $data['signer_email'] ?? null,
            'signer_phone' => $data['signer_phone'] ?? null,
            'signature_data' => $data['signature_data'],
            'compiled_content_html' => $data['compiled_content_html'] ?? null,
            'signature_type' => $data['signature_type'] ?? 'canvas_biometric',
            'ip_address' => $data['ip_address'] ?? ($this->input->ip_address() ?: '127.0.0.1'),
            'signed_at' => $now,
        ];

        $this->db->insert('waiver_signatures', $sig);
        return $this->db->insert_id();
    }

    /**
     * Get all signed waivers for a specific appointment.
     */
    public function get_appointment_signatures(int $appointment_id): array
    {
        return $this->db
            ->select('s.*, w.title as waiver_title, w.is_mandatory')
            ->from('waiver_signatures s')
            ->join('digital_waivers w', 'w.id = s.id_waivers', 'left')
            ->where('s.id_appointments', $appointment_id)
            ->order_by('s.signed_at', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Check if waiver is signed for a specific appointment.
     */
    public function is_waiver_signed(int $waiver_id, int $appointment_id): bool
    {
        return $this->db
            ->where('id_waivers', $waiver_id)
            ->where('id_appointments', $appointment_id)
            ->count_all_results('waiver_signatures') > 0;
    }

    /**
     * Attach an upsell add-on to a booking / appointment (e.g. extra VR headset, tour guide, champagne).
     */
    public function add_booking_addon(int $appointment_id, array $addon_data): int
    {
        $now = date('Y-m-d H:i:s');
        $qty = max(1, (int) ($addon_data['quantity'] ?? 1));
        $unit_price = (float) ($addon_data['unit_price'] ?? 0.00);
        $total_price = $unit_price * $qty;

        $record = [
            'id_appointments' => $appointment_id,
            'id_service_addons' => !empty($addon_data['id_service_addons']) ? (int) $addon_data['id_service_addons'] : null,
            'name' => trim($addon_data['name'] ?? 'Ekstra Deneyim Paketi'),
            'quantity' => $qty,
            'unit_price' => $unit_price,
            'total_price' => $total_price,
            'created_at' => $now,
        ];

        $this->db->insert('appointment_addons', $record);
        return $this->db->insert_id();
    }

    /**
     * Get all add-ons attached to an appointment.
     */
    public function get_booking_addons(int $appointment_id): array
    {
        return $this->db
            ->get_where('appointment_addons', ['id_appointments' => $appointment_id])
            ->result_array();
    }
}
