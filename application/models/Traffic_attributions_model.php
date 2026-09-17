<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Traffic Attributions & Ad Customer Extraction Model
 *
 * Tracks ad campaign clicks (Google Ads gclid, Meta Ads fbclid, UTM tags),
 * website heatmap events (clicks, scrolls, form interactions), click timestamps,
 * and extracts/reconstructs customer identities from ad journeys and interactions.
 * ---------------------------------------------------------------------------- */

class Traffic_attributions_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'appointment_id' => 'integer',
        'converted' => 'integer',
        'revenue' => 'float',
    ];

    /**
     * Record or update an ad click / visit session.
     */
    public function record_visit(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $sessionId = $data['session_id'] ?? bin2hex(random_bytes(16));

        $existing = $this->db->get_where('traffic_attributions', ['session_id' => $sessionId])->row_array();

        $payload = [
            'session_id' => $sessionId,
            'landing_page_slug' => $data['landing_page_slug'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'gclid' => $data['gclid'] ?? null,
            'fbclid' => $data['fbclid'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'user_agent' => !empty($data['user_agent']) ? mb_substr($data['user_agent'], 0, 255) : null,
            'ip_address' => $data['ip_address'] ?? null,
            'click_timestamp' => $data['click_timestamp'] ?? $now,
        ];

        // Filter out null values so we don't overwrite existing good UTM data on subsequent hits
        $filteredPayload = array_filter($payload, static fn($v) => $v !== null && $v !== '');

        if ($existing) {
            $this->db->update('traffic_attributions', $filteredPayload, ['id' => $existing['id']]);
            return (int) $existing['id'];
        }

        $payload['created_at'] = $now;
        $payload['heatmap_summary'] = json_encode(['clicks' => [], 'scroll_depth' => 0, 'time_on_page' => 0]);
        $payload['extracted_customer_data'] = json_encode([]);
        $payload['converted'] = 0;
        $payload['revenue'] = 0.00;

        $this->db->insert('traffic_attributions', $payload);

        return (int) $this->db->insert_id();
    }

    /**
     * Record heatmap & form interaction telemetry from tracking beacon.
     */
    public function record_interaction(string $sessionId, array $heatmapData, array $extractedClues = []): bool
    {
        $existing = $this->db->get_where('traffic_attributions', ['session_id' => $sessionId])->row_array();

        if (!$existing) {
            // Create a minimal session row first
            $this->record_visit([
                'session_id' => $sessionId,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $existing = $this->db->get_where('traffic_attributions', ['session_id' => $sessionId])->row_array();
        }

        if (!$existing) {
            return false;
        }

        // Merge heatmap summary
        $currentHeatmap = json_decode($existing['heatmap_summary'] ?? '[]', true) ?: [];
        $mergedClicks = array_merge($currentHeatmap['clicks'] ?? [], $heatmapData['clicks'] ?? []);
        // Cap clicks at 50 to prevent payload bloating
        if (count($mergedClicks) > 50) {
            $mergedClicks = array_slice($mergedClicks, -50);
        }

        $currentHeatmap['clicks'] = $mergedClicks;
        if (isset($heatmapData['scroll_depth'])) {
            $currentHeatmap['scroll_depth'] = max((int) ($currentHeatmap['scroll_depth'] ?? 0), (int) $heatmapData['scroll_depth']);
        }
        if (isset($heatmapData['time_on_page'])) {
            $currentHeatmap['time_on_page'] = max((int) ($currentHeatmap['time_on_page'] ?? 0), (int) $heatmapData['time_on_page']);
        }
        if (isset($heatmapData['last_element_clicked'])) {
            $currentHeatmap['last_element_clicked'] = $heatmapData['last_element_clicked'];
        }

        // Merge extracted customer clues
        $currentExtracted = json_decode($existing['extracted_customer_data'] ?? '[]', true) ?: [];
        foreach ($extractedClues as $key => $val) {
            if (!empty($val)) {
                $currentExtracted[$key] = $val;
            }
        }

        $updateData = [
            'heatmap_summary' => json_encode($currentHeatmap, JSON_UNESCAPED_UNICODE),
            'extracted_customer_data' => json_encode($currentExtracted, JSON_UNESCAPED_UNICODE),
        ];

        $this->db->update('traffic_attributions', $updateData, ['id' => $existing['id']]);

        // If clues contain phone or email, run extraction immediately
        if (!empty($currentExtracted['phone']) || !empty($currentExtracted['email'])) {
            $this->extract_identity((int) $existing['id']);
        }

        return true;
    }

    /**
     * Link conversion (appointment booking) to ad session attribution.
     */
    public function attribute_conversion(
        string $sessionId,
        int $customerId,
        int $appointmentId,
        float $revenue = 0.0,
        array $customer = [],
        ?string $ip = null
    ): bool {
        $attribution = null;

        if (!empty($sessionId)) {
            $attribution = $this->db->get_where('traffic_attributions', ['session_id' => $sessionId])->row_array();
        }

        // If not found by session_id, find by IP within the last 3 hours
        if (!$attribution && !empty($ip)) {
            $threeHoursAgo = date('Y-m-d H:i:s', strtotime('-3 hours'));
            $attribution = $this->db
                ->where('ip_address', $ip)
                ->where('created_at >=', $threeHoursAgo)
                ->order_by('created_at', 'DESC')
                ->get('traffic_attributions')
                ->row_array();
        }

        if (!$attribution) {
            // Create a direct attribution entry if none existed
            $this->record_visit([
                'session_id' => $sessionId ?: bin2hex(random_bytes(16)),
                'ip_address' => $ip,
                'utm_source' => 'direct',
                'utm_medium' => 'booking',
            ]);
            $attribution = $this->db->order_by('id', 'DESC')->get('traffic_attributions', 1)->row_array();
        }

        if (!$attribution) {
            return false;
        }

        $extracted = json_decode($attribution['extracted_customer_data'] ?? '[]', true) ?: [];
        $extracted['full_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $extracted['email'] = $customer['email'] ?? ($extracted['email'] ?? null);
        $extracted['phone'] = $customer['phone_number'] ?? ($extracted['phone'] ?? null);
        $extracted['match_method'] = 'Direct Booking Conversion (100% Güven)';
        $extracted['confidence'] = 100;

        $update = [
            'id_users_customer' => $customerId,
            'appointment_id' => $appointmentId,
            'converted' => 1,
            'converted_at' => date('Y-m-d H:i:s'),
            'revenue' => $revenue,
            'extracted_customer_data' => json_encode($extracted, JSON_UNESCAPED_UNICODE),
        ];

        $this->db->update('traffic_attributions', $update, ['id' => $attribution['id']]);

        // Increment landing page conversions if linked
        if (!empty($attribution['landing_page_slug'])) {
            $landingPage = $this->db->get_where('landing_pages', ['slug' => $attribution['landing_page_slug']])->row_array();
            if ($landingPage) {
                $this->db->set('conversions_count', 'conversions_count + 1', false)
                    ->where('id', $landingPage['id'])
                    ->update('landing_pages');
            }
        }

        return true;
    }

    /**
     * Get attribution logs with customer and campaign details.
     */
    public function get_attributions(int $limit = 50): array
    {
        $rows = $this->db
            ->select('ta.*, u.first_name, u.last_name, u.email as customer_email, u.phone_number as customer_phone')
            ->from('traffic_attributions ta')
            ->join('users u', 'u.id = ta.id_users_customer', 'left')
            ->order_by('ta.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $this->cast($row);
            $row['heatmap_summary'] = json_decode($row['heatmap_summary'] ?? '[]', true) ?: [];
            $row['extracted_customer_data'] = json_decode($row['extracted_customer_data'] ?? '[]', true) ?: [];

            // Detect and label ad platform
            if (!empty($row['gclid'])) {
                $row['channel_source'] = 'Google Ads';
                $row['channel_badge'] = 'primary';
            } elseif (!empty($row['fbclid'])) {
                $row['channel_source'] = 'Meta Ads / Instagram';
                $row['channel_badge'] = 'info';
            } elseif (!empty($row['utm_source'])) {
                $row['channel_source'] = ucfirst($row['utm_source']) . (!empty($row['utm_medium']) ? ' (' . $row['utm_medium'] . ')' : '');
                $row['channel_badge'] = 'secondary';
            } elseif (!empty($row['referrer'])) {
                $row['channel_source'] = 'Referans: ' . parse_url($row['referrer'], PHP_URL_HOST);
                $row['channel_badge'] = 'dark';
            } else {
                $row['channel_source'] = 'Doğrudan Ziyaret';
                $row['channel_badge'] = 'light text-dark';
            }

            // Resolved display identity
            if (!empty($row['first_name'])) {
                $row['display_identity'] = trim($row['first_name'] . ' ' . $row['last_name']);
                $row['display_phone'] = $row['customer_phone'];
                $row['identity_status'] = 'Eşleşti (Randevu)';
            } elseif (!empty($row['extracted_customer_data']['full_name']) || !empty($row['extracted_customer_data']['phone'])) {
                $row['display_identity'] = $row['extracted_customer_data']['full_name'] ?? 'İsimsiz Lead';
                $row['display_phone'] = $row['extracted_customer_data']['phone'] ?? '-';
                $row['identity_status'] = 'Çıkarıldı (%' . ($row['extracted_customer_data']['confidence'] ?? 85) . ')';
            } else {
                $row['display_identity'] = 'Anonim Ziyaretçi';
                $row['display_phone'] = '-';
                $row['identity_status'] = 'Beklemede';
            }
        }

        return $rows;
    }

    /**
     * Reconstruct and extract customer identity from ad click timestamp and interactions.
     */
    public function extract_identity(int $attributionId): array
    {
        $attribution = $this->db->get_where('traffic_attributions', ['id' => $attributionId])->row_array();

        if (!$attribution) {
            throw new InvalidArgumentException('Atıf kaydı bulunamadı.');
        }

        $extracted = json_decode($attribution['extracted_customer_data'] ?? '[]', true) ?: [];

        // 1. Check if phone is in clues and look up customer
        if (!empty($extracted['phone'])) {
            $cleanPhone = preg_replace('/[^\d]/', '', $extracted['phone']);
            $customer = $this->db
                ->like('phone_number', substr($cleanPhone, -10))
                ->get('users')
                ->row_array();

            if ($customer) {
                $extracted['full_name'] = trim($customer['first_name'] . ' ' . $customer['last_name']);
                $extracted['email'] = $customer['email'];
                $extracted['phone'] = $customer['phone_number'];
                $extracted['match_method'] = 'Form Telefon Numarası Eşleştirmesi';
                $extracted['confidence'] = 98;

                $this->db->update('traffic_attributions', [
                    'id_users_customer' => $customer['id'],
                    'extracted_customer_data' => json_encode($extracted, JSON_UNESCAPED_UNICODE),
                ], ['id' => $attributionId]);

                return $extracted;
            }
        }

        // 2. Check email in clues
        if (!empty($extracted['email'])) {
            $customer = $this->db->get_where('users', ['email' => $extracted['email']])->row_array();
            if ($customer) {
                $extracted['full_name'] = trim($customer['first_name'] . ' ' . $customer['last_name']);
                $extracted['email'] = $customer['email'];
                $extracted['phone'] = $customer['phone_number'];
                $extracted['match_method'] = 'Form E-Posta Eşleştirmesi';
                $extracted['confidence'] = 99;

                $this->db->update('traffic_attributions', [
                    'id_users_customer' => $customer['id'],
                    'extracted_customer_data' => json_encode($extracted, JSON_UNESCAPED_UNICODE),
                ], ['id' => $attributionId]);

                return $extracted;
            }
        }

        // 3. Correlate by IP and Click Timestamp (within 1 hour)
        if (!empty($attribution['ip_address'])) {
            $clickTime = strtotime($attribution['click_timestamp'] ?? $attribution['created_at']);
            $timeStart = date('Y-m-d H:i:s', $clickTime - 3600);
            $timeEnd = date('Y-m-d H:i:s', $clickTime + 3600);

            // Find consent or appointment logged from same IP in time window
            $consent = $this->db
                ->where('ip', $attribution['ip_address'])
                ->where('created >=', $timeStart)
                ->where('created <=', $timeEnd)
                ->order_by('created', 'DESC')
                ->get('consents')
                ->row_array();

            if ($consent && !empty($consent['email']) && $consent['email'] !== '-') {
                $extracted['full_name'] = trim($consent['first_name'] . ' ' . $consent['last_name']);
                $extracted['email'] = $consent['email'];
                $extracted['match_method'] = 'IP & Tıklama Zamanı Parmak İzi Korelasyonu';
                $extracted['confidence'] = 90;

                // Check customer table for full record
                $customer = $this->db->get_where('users', ['email' => $consent['email']])->row_array();
                if ($customer) {
                    $extracted['phone'] = $customer['phone_number'];
                    $this->db->update('traffic_attributions', ['id_users_customer' => $customer['id']], ['id' => $attributionId]);
                }

                $this->db->update('traffic_attributions', [
                    'extracted_customer_data' => json_encode($extracted, JSON_UNESCAPED_UNICODE),
                ], ['id' => $attributionId]);

                return $extracted;
            }
        }

        // If no match found, calculate heuristic profile from heatmap & campaign
        $heatmap = json_decode($attribution['heatmap_summary'] ?? '[]', true) ?: [];
        $clickCount = count($heatmap['clicks'] ?? []);
        $timeSpent = $heatmap['time_on_page'] ?? 0;

        $extracted['status'] = 'Kimlik doğrudan eşleşmedi';
        $extracted['ad_journey'] = 'Tıklama Saati: ' . ($attribution['click_timestamp'] ?? '-') . ' | Etkileşim: ' . $clickCount . ' tıklama, ' . $timeSpent . ' sn';
        $extracted['confidence'] = 40;

        $this->db->update('traffic_attributions', [
            'extracted_customer_data' => json_encode($extracted, JSON_UNESCAPED_UNICODE),
        ], ['id' => $attributionId]);

        return $extracted;
    }
}
