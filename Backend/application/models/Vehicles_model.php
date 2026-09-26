<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Customer Vehicles Management Model (Tekmetric / Shopmonkey / PratikServis style)
 * ---------------------------------------------------------------------------- */

class Vehicles_model extends App_Model
{
    /**
     * Normalize plate number (e.g. "34 ABC 123" -> "34ABC123").
     */
    public function normalize_plate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    /**
     * Add or register a customer vehicle.
     */
    public function add_vehicle(array $data): int
    {
        if (empty($data['id_users_customer']) || empty($data['plate_number'])) {
            throw new InvalidArgumentException('Müşteri ve araç plakası zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $vehicle = [
            'id_users_customer' => (int) $data['id_users_customer'],
            'plate_number' => $this->normalize_plate($data['plate_number']),
            'vin' => !empty($data['vin']) ? strtoupper(trim($data['vin'])) : null,
            'brand' => trim($data['brand'] ?? 'Bilinmiyor'),
            'model' => trim($data['model'] ?? 'Bilinmiyor'),
            'year' => !empty($data['year']) ? (int) $data['year'] : null,
            'color' => $data['color'] ?? null,
            'current_km' => max(0, (int) ($data['current_km'] ?? 0)),
            'fuel_type' => $data['fuel_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('customer_vehicles', $vehicle);
        return $this->db->insert_id();
    }

    /**
     * Find vehicle by plate number.
     */
    public function get_by_plate(string $plate): ?array
    {
        $normalized = $this->normalize_plate($plate);
        return $this->db->get_where('customer_vehicles', ['plate_number' => $normalized])->row_array();
    }

    /**
     * Get all vehicles belonging to a customer.
     */
    public function get_by_customer(int $customer_id): array
    {
        return $this->db
            ->get_where('customer_vehicles', ['id_users_customer' => $customer_id])
            ->result_array();
    }

    /**
     * Update vehicle details or mileage (km).
     */
    public function update_vehicle(int $vehicle_id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $update = ['updated_at' => $now];

        $allowed = ['vin', 'brand', 'model', 'year', 'color', 'current_km', 'fuel_type', 'notes'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }
        if (!empty($data['plate_number'])) {
            $update['plate_number'] = $this->normalize_plate($data['plate_number']);
        }

        return $this->db->update('customer_vehicles', $update, ['id' => $vehicle_id]);
    }
}
