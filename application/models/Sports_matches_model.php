<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Sports Courts, Matchmaking & Turnstile Access Model
 * (Playtomic / Glofox / CourtReserve / SahaBurada style)
 * ---------------------------------------------------------------------------- */

class Sports_matches_model extends EA_Model
{
    /**
     * Create an open or private court match.
     */
    public function create_match(array $data): int
    {
        if (empty($data['title']) || empty($data['start_datetime']) || empty($data['end_datetime'])) {
            throw new InvalidArgumentException('Maç başlığı, başlangıç ve bitiş zamanı zorunludur.');
        }

        $now = date('Y-m-d H:i:s');
        $match = [
            'title' => trim($data['title']),
            'id_stations' => !empty($data['id_stations']) ? (int) $data['id_stations'] : null,
            'id_services' => !empty($data['id_services']) ? (int) $data['id_services'] : null,
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'match_type' => $data['match_type'] ?? 'open',
            'sport_type' => $data['sport_type'] ?? 'padel',
            'level_required' => $data['level_required'] ?? 'all',
            'max_players' => max(2, (int) ($data['max_players'] ?? 4)),
            'current_players' => 1,
            'price_per_player' => (float) ($data['price_per_player'] ?? 0.00),
            'status' => 'open',
            'created_by_user_id' => (int) ($data['created_by_user_id'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->trans_start();
        $this->db->insert('sports_court_matches', $match);
        $match_id = $this->db->insert_id();

        // Creator automatically joins as first participant
        $this->db->insert('sports_match_participants', [
            'id_matches' => $match_id,
            'id_users_customer' => (int) $match['created_by_user_id'],
            'payment_status' => 'paid',
            'team' => 'Team A',
            'skill_level' => $data['creator_skill_level'] ?? null,
            'joined_at' => $now,
        ]);

        $this->db->trans_complete();
        return $match_id;
    }

    /**
     * Get available open matches for matchmaking.
     */
    public function get_open_matches(?string $sport_type = null): array
    {
        $this->db
            ->select('m.*, st.name as court_name, u.first_name as host_first_name, u.last_name as host_last_name')
            ->from('sports_court_matches m')
            ->join('stations st', 'st.id = m.id_stations', 'left')
            ->join('users u', 'u.id = m.created_by_user_id', 'left')
            ->where('m.status', 'open')
            ->where('m.start_datetime >=', date('Y-m-d H:i:s'))
            ->order_by('m.start_datetime ASC');

        if ($sport_type) {
            $this->db->where('m.sport_type', $sport_type);
        }

        $matches = $this->db->get()->result_array();

        foreach ($matches as &$m) {
            $m['participants'] = $this->get_participants((int) $m['id']);
        }

        return $matches;
    }

    /**
     * Join an open match.
     */
    public function join_match(int $match_id, int $customer_id, ?string $team = null, ?string $skill_level = null): array
    {
        $match = $this->db->get_where('sports_court_matches', ['id' => $match_id])->row_array();
        if (!$match) {
            return ['success' => false, 'message' => 'Maç bulunamadı.'];
        }

        if ($match['status'] !== 'open') {
            return ['success' => false, 'message' => 'Maç katılıma açık değil (Durum: ' . $match['status'] . ').'];
        }

        // Check if already joined
        $already = $this->db->get_where('sports_match_participants', [
            'id_matches' => $match_id,
            'id_users_customer' => $customer_id,
        ])->num_rows() > 0;

        if ($already) {
            return ['success' => false, 'message' => 'Bu maça zaten katıldınız.'];
        }

        $current_count = (int) $match['current_players'];
        $max_count = (int) $match['max_players'];

        if ($current_count >= $max_count) {
            return ['success' => false, 'message' => 'Maç kontenjanı dolmuştur.'];
        }

        $this->db->trans_start();

        $this->db->insert('sports_match_participants', [
            'id_matches' => $match_id,
            'id_users_customer' => $customer_id,
            'payment_status' => ((float) $match['price_per_player'] > 0) ? 'pending' : 'paid',
            'team' => $team ?? ($current_count % 2 === 0 ? 'Team A' : 'Team B'),
            'skill_level' => $skill_level,
            'joined_at' => date('Y-m-d H:i:s'),
        ]);

        $new_count = $current_count + 1;
        $new_status = ($new_count >= $max_count) ? 'full' : 'open';

        $this->db->update('sports_court_matches', [
            'current_players' => $new_count,
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $match_id]);

        $this->db->trans_complete();

        return [
            'success' => true,
            'message' => 'Maça başarıyla katıldınız!',
            'current_players' => $new_count,
            'status' => $new_status,
        ];
    }

    /**
     * Get participants of a match.
     */
    public function get_participants(int $match_id): array
    {
        return $this->db
            ->select('p.*, u.first_name, u.last_name, u.phone_number')
            ->from('sports_match_participants p')
            ->join('users u', 'u.id = p.id_users_customer', 'left')
            ->where('p.id_matches', $match_id)
            ->get()
            ->result_array();
    }

    /**
     * Turnike & Access Control validation for gym, court, turnstile hardware.
     * Evaluates access token / QR / PIN code and returns instant relay grant/deny decision.
     */
    public function verify_turnstile_access(string $access_token, ?string $gate_id = null): array
    {
        $this->load->model('checkin_model');
        $resolved = $this->checkin_model->resolve_identifier($access_token);

        if (!$resolved['customer_id'] && !$resolved['appointment_id'] && !$resolved['membership_id']) {
            return [
                'access_granted' => false,
                'relay_trigger' => 0,
                'reason' => 'Geçersiz bilet/kart/QR kod.',
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        // Check active membership or confirmed appointment starting within 30 minutes
        $now = date('Y-m-d H:i:s');
        $granted = false;
        $reason = 'Giriş izni onaylandı.';
        $customer_name = 'Misafir';

        if ($resolved['customer_id']) {
            $cust = $this->db->get_where('users', ['id' => $resolved['customer_id']])->row_array();
            if ($cust) {
                $customer_name = trim($cust['first_name'] . ' ' . $cust['last_name']);
            }
        }

        // 1. Membership check
        if ($resolved['membership_id'] || $resolved['customer_id']) {
            $cid = $resolved['customer_id'];
            $active_memb = $this->db
                ->where('id_users_customer', $cid)
                ->where('status', 'active')
                ->where('current_period_end >=', date('Y-m-d'))
                ->get('customer_memberships')
                ->row_array();

            if ($active_memb) {
                $granted = true;
                $reason = 'Aktif üyelik doğrulandı: ' . $customer_name;
            }
        }

        // 2. Appointment / Court slot check (window: -30 mins before start to +30 mins after end)
        if (!$granted && ($resolved['appointment_id'] || $resolved['customer_id'])) {
            $this->db
                ->from('appointments')
                ->where('is_unavailability', 0)
                ->where_not_in('status', ['Cancelled', 'Draft'])
                ->where('start_datetime <=', date('Y-m-d H:i:s', strtotime('+30 minutes')))
                ->where('end_datetime >=', date('Y-m-d H:i:s', strtotime('-30 minutes')));

            if ($resolved['appointment_id']) {
                $this->db->where('id', $resolved['appointment_id']);
            } else {
                $this->db->where('id_users_customer', $resolved['customer_id']);
            }

            $appt = $this->db->get()->row_array();
            if ($appt) {
                $granted = true;
                $reason = 'Rezervasyon/kort randevusu doğrulandı: ' . $customer_name;
            }
        }

        // Record check-in log
        $this->db->insert('checkin_logs', [
            'id_users_customer' => $resolved['customer_id'],
            'id_appointments' => $resolved['appointment_id'],
            'checkin_method' => 'turnstile_gate',
            'entry_timestamp' => $now,
            'status' => $granted ? 'completed' : 'denied',
            'notes' => ($granted ? 'İzin Verildi' : 'Engellendi') . ($gate_id ? ' [Kapı: ' . $gate_id . ']' : ''),
            'created_at' => $now,
        ]);

        return [
            'access_granted' => $granted,
            'relay_trigger' => $granted ? 1 : 0, // 1 triggers turnstile relay pulse
            'pulse_duration_ms' => $granted ? 1500 : 0,
            'customer_name' => $customer_name,
            'gate_id' => $gate_id ?? 'default_gate',
            'reason' => $reason,
            'timestamp' => $now,
        ];
    }
}
