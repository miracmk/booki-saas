<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Event Tickets & Pass Generator Model (Biletinial / FareHarbor style)
 * ---------------------------------------------------------------------------- */

class Event_tickets_model extends App_Model
{
    /**
     * Generate unique ticket code (e.g. TKT-7A4B-9C2D).
     */
    public function generate_ticket_code(): string
    {
        do {
            $code = 'TKT-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $exists = $this->db->get_where('event_tickets', ['ticket_code' => $code])->num_rows() > 0;
        } while ($exists);

        return $code;
    }

    /**
     * Generate and issue an event/session admission ticket.
     */
    public function issue_ticket(int $appointment_id, int $customer_id, ?string $seat_or_slot_label = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $this->generate_ticket_code();

        $ticket = [
            'ticket_code' => $code,
            'id_appointments' => $appointment_id,
            'id_users_customer' => $customer_id,
            'seat_or_slot_label' => $seat_or_slot_label,
            'status' => 'valid',
            'used_at' => null,
            'created_at' => $now,
        ];

        $this->db->insert('event_tickets', $ticket);
        $ticket['id'] = $this->db->insert_id();

        return $ticket;
    }

    /**
     * Validate and scan/burn admission ticket at gate.
     */
    public function validate_ticket(string $ticket_code): array
    {
        $code = strtoupper(trim($ticket_code));
        $ticket = $this->db
            ->select('t.*, a.start_datetime, a.end_datetime, s.name as service_name, c.first_name, c.last_name, c.phone_number')
            ->from('event_tickets t')
            ->join('appointments a', 'a.id = t.id_appointments', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users c', 'c.id = t.id_users_customer', 'left')
            ->where('t.ticket_code', $code)
            ->get()
            ->row_array();

        if (!$ticket) {
            return [
                'valid' => false,
                'status' => 'not_found',
                'message' => 'Geçersiz bilet kodu.',
            ];
        }

        if ($ticket['status'] === 'used') {
            return [
                'valid' => false,
                'status' => 'already_used',
                'message' => 'Bu bilet daha önce kullanılmıştır (' . $ticket['used_at'] . ').',
                'ticket' => $ticket,
            ];
        }

        if ($ticket['status'] === 'cancelled') {
            return [
                'valid' => false,
                'status' => 'cancelled',
                'message' => 'Bu bilet iptal edilmiştir.',
                'ticket' => $ticket,
            ];
        }

        // Mark as used
        $now = date('Y-m-d H:i:s');
        $this->db->update('event_tickets', [
            'status' => 'used',
            'used_at' => $now,
        ], ['id' => $ticket['id']]);

        $ticket['status'] = 'used';
        $ticket['used_at'] = $now;

        return [
            'valid' => true,
            'status' => 'success',
            'message' => 'Bilet başarıyla doğrulandı ve giriş sağlandı.',
            'ticket' => $ticket,
        ];
    }
}
