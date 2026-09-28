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
        $record = [
            'id_users_customer' => (int) $data['id_users_customer'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_users_provider' => (int) $data['id_users_provider'],
            'record_type' => $data['record_type'] ?? 'soap_note',
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'attachments_json' => !empty($data['attachments']) ? json_encode($data['attachments']) : null,
            'is_confidential' => isset($data['is_confidential']) ? (int) $data['is_confidential'] : 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('clinical_records', $record);
        return $this->db->insert_id();
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

    /* -------------------------------------------------------------------------
     * EMR Vitals, Prescriptions, Allergies & Lab Orders (OpenMRS/Bahmni standard)
     * ------------------------------------------------------------------------- */

    /**
     * Get patient vitals history (BP, pulse, temp, BMI, SpO2, blood glucose).
     */
    public function get_patient_vitals(int $customer_id, int $limit = 20): array
    {
        return $this->db
            ->select('pv.*, u.first_name as recorded_by_first_name, u.last_name as recorded_by_last_name')
            ->from('patient_vitals pv')
            ->join('users u', 'u.id = pv.recorded_by_user_id', 'left')
            ->where('pv.id_users_patient', $customer_id)
            ->order_by('pv.recorded_at DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Save patient vitals measurement.
     */
    public function save_patient_vitals(array $data): int
    {
        $weight = !empty($data['weight_kg']) ? (float) $data['weight_kg'] : null;
        $height = !empty($data['height_cm']) ? (float) $data['height_cm'] : null;
        $bmi = null;

        if ($weight && $height && $height > 0) {
            $height_m = $height / 100.0;
            $bmi = round($weight / ($height_m * $height_m), 1);
        }

        $record = [
            'id_users_patient' => (int) $data['id_users_patient'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'systolic_bp' => !empty($data['systolic_bp'] ?? $data['blood_pressure_systolic'] ?? null) ? (int) ($data['systolic_bp'] ?? $data['blood_pressure_systolic']) : null,
            'diastolic_bp' => !empty($data['diastolic_bp'] ?? $data['blood_pressure_diastolic'] ?? null) ? (int) ($data['diastolic_bp'] ?? $data['blood_pressure_diastolic']) : null,
            'pulse_rate' => !empty($data['pulse_rate'] ?? $data['pulse_bpm'] ?? null) ? (int) ($data['pulse_rate'] ?? $data['pulse_bpm']) : null,
            'temperature_c' => !empty($data['temperature_c'] ?? $data['body_temperature_c'] ?? null) ? (float) ($data['temperature_c'] ?? $data['body_temperature_c']) : null,
            'respiratory_rate' => !empty($data['respiratory_rate']) ? (int) $data['respiratory_rate'] : null,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'bmi' => $bmi,
            'spo2_percent' => !empty($data['spo2_percent'] ?? $data['blood_oxygen_spo2'] ?? null) ? (int) ($data['spo2_percent'] ?? $data['blood_oxygen_spo2']) : null,
            'blood_glucose' => !empty($data['blood_glucose']) ? (float) $data['blood_glucose'] : null,
            'recorded_by_user_id' => !empty($data['recorded_by_user_id'] ?? $data['recorded_by'] ?? null) ? (int) ($data['recorded_by_user_id'] ?? $data['recorded_by']) : (int) session('user_id'),
            'notes' => $data['notes'] ?? null,
            'recorded_at' => !empty($data['recorded_at']) ? $data['recorded_at'] : date('Y-m-d H:i:s'),
        ];

        $this->db->insert('patient_vitals', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Get patient prescriptions.
     */
    public function get_patient_prescriptions(int $customer_id, int $limit = 50): array
    {
        return $this->db
            ->select('pp.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name')
            ->from('patient_prescriptions pp')
            ->join('users d', 'd.id = pp.id_users_doctor', 'left')
            ->where('pp.id_users_patient', $customer_id)
            ->order_by('pp.prescribed_at DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Save prescription for patient.
     */
    public function save_patient_prescription(array $data): int
    {
        $record = [
            'id_users_patient' => (int) $data['id_users_patient'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_users_doctor' => !empty($data['id_users_doctor'] ?? $data['prescribed_by'] ?? null) ? (int) ($data['id_users_doctor'] ?? $data['prescribed_by']) : (int) session('user_id'),
            'medication_name' => trim($data['medication_name']),
            'dosage' => trim($data['dosage'] ?? '1 tablet'),
            'frequency' => trim($data['frequency'] ?? 'Günde 2 defa'),
            'duration_days' => !empty($data['duration_days']) ? (int) $data['duration_days'] : 7,
            'instructions' => $data['instructions'] ?? null,
            'prescribed_at' => !empty($data['prescribed_at']) ? $data['prescribed_at'] : date('Y-m-d H:i:s'),
        ];

        $this->db->insert('patient_prescriptions', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Get known allergies for patient.
     */
    public function get_patient_allergies(int $customer_id): array
    {
        return $this->db
            ->where('id_users_patient', $customer_id)
            ->order_by('severity DESC, allergen ASC')
            ->get('patient_allergies')
            ->result_array();
    }

    public function save_patient_allergy(array $data): int
    {
        $record = [
            'id_users_patient' => (int) $data['id_users_patient'],
            'allergen' => trim($data['allergen'] ?? $data['allergen_name'] ?? ''),
            'severity' => in_array($data['severity'] ?? '', ['mild', 'moderate', 'severe'], true) ? $data['severity'] : 'moderate',
            'reaction_notes' => $data['reaction_notes'] ?? $data['reaction_description'] ?? null,
            'identified_at' => !empty($data['identified_at']) ? $data['identified_at'] : date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('patient_allergies', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_patient_allergy(int $id): bool
    {
        return $this->db->where('id', $id)->delete('patient_allergies');
    }

    /**
     * Get lab orders & diagnostic test results.
     */
    public function get_clinical_lab_orders(int $customer_id, int $limit = 50): array
    {
        return $this->db
            ->where('id_users_patient', $customer_id)
            ->order_by('created_at DESC')
            ->limit($limit)
            ->get('clinical_lab_orders')
            ->result_array();
    }

    public function save_clinical_lab_order(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_users_patient' => (int) $data['id_users_patient'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'test_name' => trim($data['test_name']),
            'category' => $data['category'] ?? 'biochemistry',
            'status' => $data['status'] ?? 'requested',
            'result_summary' => $data['result_summary'] ?? null,
            'normal_range' => $data['normal_range'] ?? null,
            'is_abnormal' => !empty($data['is_abnormal']) ? 1 : 0,
            'result_attachment' => $data['result_attachment'] ?? null,
            'completed_at' => ($data['status'] ?? '') === 'completed' ? $now : null,
            'created_at' => $now,
        ];

        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($record['created_at']);
            $this->db->where('id', $id)->update('clinical_lab_orders', $record);
            return $id;
        }

        $this->db->insert('clinical_lab_orders', $record);
        return (int) $this->db->insert_id();
    }
}

