<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Work Orders (İş Emri) & DVI (Digital Vehicle Inspection) Model
 * (Tekmetric / AutoLeap / Otomax / PratikServis style)
 * ---------------------------------------------------------------------------- */

class Work_orders_model extends App_Model
{
    /**
     * Generate unique work order number (e.g. WO-2026-0042).
     */
    public function generate_work_order_number(): string
    {
        $year = date('Y');
        $count = $this->db->where('created_at >=', $year . '-01-01 00:00:00')->count_all_results('work_orders');
        return sprintf('WO-%s-%04d', $year, $count + 1);
    }

    /**
     * Create a repair / detailing work order.
     */
    public function create_work_order(array $data): array
    {
        if (empty($data['id_vehicles'])) {
            throw new InvalidArgumentException('Araç seçimi zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $wo_number = !empty($data['work_order_number']) ? $data['work_order_number'] : $this->generate_work_order_number();

        $wo = [
            'work_order_number' => $wo_number,
            'id_vehicles' => (int) $data['id_vehicles'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_users_technician' => !empty($data['id_users_technician']) ? (int) $data['id_users_technician'] : null,
            'status' => $data['status'] ?? 'created',
            'estimated_cost' => (float) ($data['estimated_cost'] ?? 0.00),
            'final_cost' => (float) ($data['final_cost'] ?? 0.00),
            'labor_items_json' => !empty($data['labor_items']) ? json_encode($data['labor_items']) : null,
            'parts_items_json' => !empty($data['parts_items']) ? json_encode($data['parts_items']) : null,
            'delivery_datetime' => !empty($data['delivery_datetime']) ? $data['delivery_datetime'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('work_orders', $wo);
        $wo['id'] = $this->db->insert_id();

        return $wo;
    }

    /**
     * Update work order status through the workshop pipeline:
     * created -> inspected -> estimate_pending -> approved -> in_progress -> parts_waiting -> quality_check -> ready -> delivered
     */
    public function update_status(int $work_order_id, string $status): bool
    {
        $valid = [
            'created', 'inspected', 'estimate_pending', 'approved',
            'in_progress', 'parts_waiting', 'quality_check', 'ready', 'delivered'
        ];

        if (!in_array($status, $valid, true)) {
            throw new InvalidArgumentException('Geçersiz iş emri durumu: ' . $status);
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'status' => $status,
            'updated_at' => $now,
        ];

        if ($status === 'delivered') {
            $data['delivery_datetime'] = $now;
        }

        return $this->db->update('work_orders', $data, ['id' => $work_order_id]);
    }

    /**
     * Digital Vehicle Inspection (DVI) / Dijital Araç Ekspertiz & Muayene Formu.
     */
    public function save_inspection(array $data): array
    {
        if (empty($data['id_vehicles'])) {
            throw new InvalidArgumentException('Ekspertiz için araç seçimi zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $token = bin2hex(random_bytes(16)); // 32-char secure public share token

        $inspection = [
            'id_vehicles' => (int) $data['id_vehicles'],
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'inspector_id' => !empty($data['inspector_id']) ? (int) $data['inspector_id'] : null,
            'inspection_type' => $data['inspection_type'] ?? 'general_service',
            'overall_score' => isset($data['overall_score']) ? (int) $data['overall_score'] : null,
            'items_json' => !empty($data['items']) ? json_encode($data['items']) : null,
            'customer_shared_token' => $token,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('vehicle_inspections', $inspection);
        $inspection['id'] = $this->db->insert_id();

        return $inspection;
    }

    /**
     * Get inspection report by customer share token.
     */
    public function get_inspection_by_token(string $token): ?array
    {
        $inspection = $this->db
            ->select('vi.*, v.plate_number, v.brand, v.model, v.year, v.color, v.current_km, u.first_name as inspector_first_name, u.last_name as inspector_last_name')
            ->from('vehicle_inspections vi')
            ->join('customer_vehicles v', 'v.id = vi.id_vehicles', 'left')
            ->join('users u', 'u.id = vi.inspector_id', 'left')
            ->where('vi.customer_shared_token', $token)
            ->get()
            ->row_array();

        if ($inspection && !empty($inspection['items_json'])) {
            $inspection['items'] = json_decode($inspection['items_json'], true);
        }

        return $inspection;
    }

    /**
     * Customer digitally approves the vehicle inspection / estimate.
     */
    public function approve_inspection_by_token(string $token): bool
    {
        return $this->db->update('vehicle_inspections', [
            'customer_approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['customer_shared_token' => $token]);
    }
}
