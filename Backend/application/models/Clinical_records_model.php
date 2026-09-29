<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Clinical Records & EHR Model (SimplePractice / Jane App / BulutKlinik style)
 * ---------------------------------------------------------------------------- */

class Clinical_records_model extends App_Model
{
    /**
     * Add a clinical charting note (SOAP, Anamnesis, Prescription, Lab, Vet examination).
     */
    public function add_record(array $data): int
    {
        if (empty($data['id_users_customer']) || empty($data['id_users_provider'])) {
            throw new InvalidArgumentException('Danışan/hasta ve hekim/uzman seçimi zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $is_confidential = isset($data['is_confidential']) ? ((in_array($data['is_confidential'], [1, '1', true, 'true', 'on'], true)) ? 1 : 0) : 1;
        $id_appointments = !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null;

        $record = [
            'id_users_customer' => (int) $data['id_users_customer'],
            'id_appointments' => $id_appointments,
            'id_users_provider' => (int) $data['id_users_provider'],
            'record_type' => $data['record_type'] ?? 'soap_note',
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'attachments_json' => !empty($data['attachments']) ? json_encode($data['attachments']) : null,
            'is_confidential' => $is_confidential,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('clinical_records', $record);
        return $this->db->insert_id();
    }

    /**
     * Get single clinical record by ID with complete patient, provider, and appointment details.
     */
    public function get_record_by_id(int $id): ?array
    {
        $record = $this->db
            ->select('cr.*, c.first_name as patient_first_name, c.last_name as patient_last_name, c.phone_number as patient_phone, c.phone_number, c.phone_number as phone, p.first_name as provider_first_name, p.last_name as provider_last_name, p.first_name as doc_first_name, p.last_name as doc_last_name, a.start_datetime as appointment_start_datetime, a.end_datetime as appointment_end_datetime, a.start_datetime as appointment_date')
            ->from('clinical_records cr')
            ->join('users c', 'c.id = cr.id_users_customer', 'left')
            ->join('users p', 'p.id = cr.id_users_provider', 'left')
            ->join('appointments a', 'a.id = cr.id_appointments', 'left')
            ->where('cr.id', $id)
            ->get()
            ->row_array();

        if (!$record) {
            return null;
        }

        $record['attachments'] = !empty($record['attachments_json']) ? json_decode($record['attachments_json'], true) : [];

        return $record;
    }

    /**
     * Get recent appointments for patient for quick linking to clinical charting notes.
     */
    public function get_patient_appointments(int $customer_id, int $limit = 50): array
    {
        return $this->db
            ->select('a.id, a.start_datetime, a.end_datetime, a.id_services, a.id_users_provider, a.notes, a.is_unavailability, s.name as service_name, p.first_name as provider_first_name, p.last_name as provider_last_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users p', 'p.id = a.id_users_provider', 'left')
            ->where('a.id_users_customer', $customer_id)
            ->where('a.is_unavailability', 0)
            ->order_by('a.start_datetime DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Update an existing clinical record.
     */
    public function update_record(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $update = [
            'updated_at' => $now,
        ];

        $fields = ['record_type', 'subjective', 'objective', 'assessment', 'plan', 'is_confidential'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }
        if (isset($data['attachments'])) {
            $update['attachments_json'] = json_encode($data['attachments']);
        }

        return $this->db->update('clinical_records', $update, ['id' => $id]);
    }

    /**
     * Get patient clinical history / timeline.
     */
    public function get_patient_history(int $customer_id, bool $include_confidential = true): array
    {
        $this->db
            ->select('cr.*, u.first_name as provider_first_name, u.last_name as provider_last_name, a.start_datetime as appointment_date')
            ->from('clinical_records cr')
            ->join('users u', 'u.id = cr.id_users_provider', 'left')
            ->join('appointments a', 'a.id = cr.id_appointments', 'left')
            ->where('cr.id_users_customer', $customer_id)
            ->order_by('cr.created_at DESC');

        if (!$include_confidential) {
            $this->db->where('cr.is_confidential', 0);
        }

        $records = $this->db->get()->result_array();
        foreach ($records as &$r) {
            $r['attachments'] = !empty($r['attachments_json']) ? json_decode($r['attachments_json'], true) : [];
        }

        return $records;
    }

    /**
     * Alias for get_patient_history.
     */
    public function get_patient_records(int $customer_id): array
    {
        return $this->get_patient_history($customer_id);
    }

    /**
     * Get or create insurance profile for patient.
     */
    public function save_patient_insurance(int $customer_id, array $data): int
    {
        $existing = $this->db->get_where('patient_insurances', ['id_users_customer' => $customer_id])->row_array();
        $now = date('Y-m-d H:i:s');

        $record = [
            'provider_name' => trim($data['provider_name'] ?? 'SGK'),
            'policy_number' => trim($data['policy_number'] ?? ''),
            'valid_until' => !empty($data['valid_until']) ? $data['valid_until'] : null,
            'coverage_ratio' => max(0, min(100, (int) ($data['coverage_ratio'] ?? 100))),
            'notes' => $data['notes'] ?? null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->update('patient_insurances', $record, ['id' => $existing['id']]);
            return (int) $existing['id'];
        } else {
            $record['id_users_customer'] = $customer_id;
            $record['created_at'] = $now;
            $this->db->insert('patient_insurances', $record);
            return $this->db->insert_id();
        }
    }

    /**
     * Get insurance info for patient.
     */
    public function get_patient_insurance(int $customer_id): ?array
    {
        return $this->db->get_where('patient_insurances', ['id_users_customer' => $customer_id])->row_array();
    }

    /**
     * Generate or launch Telehealth meeting link for an appointment.
     */
    public function get_or_create_telehealth_link(int $appointment_id): string
    {
        $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
        if (!$appt) {
            throw new InvalidArgumentException('Randevu bulunamadı.');
        }

        if (!empty($appt['meeting_link'])) {
            return $appt['meeting_link'];
        }

        // Generate secure telehealth video room (Jitsi / BooKi Telehealth)
        $room_id = 'booki-telehealth-' . substr(md5($appt['id'] . '-' . $appt['hash']), 0, 16);
        $link = 'https://meet.jit.si/' . $room_id;

        $this->db->update('appointments', [
            'meeting_link' => $link,
            'update_datetime' => date('Y-m-d H:i:s'),
        ], ['id' => $appointment_id]);

        return $link;
    }

    /**
     * Generate secure telehealth video room session.
     */
    public function generate_telehealth_session(?int $appointment_id = null, string $doctor_name = '', string $patient_name = ''): array
    {
        $room_id = 'booki-telehealth-' . bin2hex(random_bytes(8));
        $room_url = 'https://meet.jit.si/' . $room_id;

        if ($appointment_id) {
            $this->db->update('appointments', [
                'meeting_link' => $room_url,
                'update_datetime' => date('Y-m-d H:i:s'),
            ], ['id' => $appointment_id]);
        }

        return [
            'room_id' => $room_id,
            'room_url' => $room_url,
            'doctor_name' => $doctor_name,
            'patient_name' => $patient_name,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
}
