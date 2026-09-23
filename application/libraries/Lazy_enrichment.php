<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Lazy Enrichment Library for Marketplace pSEO
 *
 * Performs on-demand Google Places API (New) details retrieval, photo
 * references extraction, social media (Instagram) scraping, and lead state
 * updates on page visit.
 * -------------------------------------------------------------------------- */

class Lazy_enrichment
{
    protected $CI;

    // Google Places API (New) details field mask
    public const ENRICHMENT_FIELD_MASK = 'id,displayName,nationalPhoneNumber,internationalPhoneNumber,websiteUri,rating,userRatingCount,regularOpeningHours,currentOpeningHours,priceLevel,photos,reviews,reservable,paymentOptions,formattedAddress,location,addressComponents,googleMapsUri';

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Get API key with fallback
     */
    protected function get_api_key(): string
    {
        $key = '';
        if (function_exists('master_setting')) {
            $key = (string) master_setting('google_maps_key');
        }
        if (empty($key)) {
            $key = (string) (getenv('GOOGLE_PLACES_API_KEY') ?: 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA');
        }
        return $key;
    }

    /**
     * Enrich a single lead on page visit. Returns enriched lead data array.
     * If already enriched, returns existing lead directly from DB.
     * If raw_lead with google_place_id or place_id, calls Google Places Details API.
     */
    public function enrich_on_visit(int $lead_id): ?array
    {
        $lead = $this->CI->db->get_where('leads', ['id' => $lead_id])->row_array();
        if (!$lead) {
            return null;
        }

        // Already enriched — serve from DB directly
        if (($lead['enrichment_status'] ?? 'raw_lead') === 'enriched_lead') {
            return $lead;
        }

        $place_id = $lead['google_place_id'] ?: ($lead['place_id'] ?? '');
        if (empty($place_id)) {
            return $lead;
        }

        try {
            $details = $this->fetch_place_details($place_id);
            if (!$details) {
                return $lead;
            }

            $updates = $this->map_details_to_updates($details, $lead);
            $updates['enrichment_status'] = 'enriched_lead';
            $updates['enriched_at'] = date('Y-m-d H:i:s');
            $updates['updated_at'] = date('Y-m-d H:i:s');

            $this->CI->db->where('id', $lead_id)->update('leads', $updates);

            $this->log_api_usage($place_id, 'lazy_enrichment');

            return array_merge($lead, $updates);
        } catch (Throwable $e) {
            log_message('error', 'Lazy enrichment failed for lead #' . $lead_id . ': ' . $e->getMessage());
            return $lead;
        }
    }

    /**
     * Fetch Place Details from Google Places API (New).
     */
    public function fetch_place_details(string $place_id): ?array
    {
        $api_key = $this->get_api_key();
        if ($api_key === '') {
            return null;
        }

        $url = 'https://places.googleapis.com/v1/places/' . urlencode($place_id);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $api_key,
                'X-Goog-FieldMask: ' . self::ENRICHMENT_FIELD_MASK,
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Map Google Places API response to lead table update fields.
     */
    protected function map_details_to_updates(array $details, array $lead): array
    {
        $updates = [];

        // Display Name if empty
        if (empty($lead['name']) && !empty($details['displayName']['text'])) {
            $updates['name'] = $details['displayName']['text'];
        }

        // Phone & WhatsApp
        $phone = $details['nationalPhoneNumber'] ?? ($details['internationalPhoneNumber'] ?? null);
        if ($phone) {
            if (empty($lead['phone'])) {
                $updates['phone'] = $phone;
            }
            $clean = preg_replace('/[^0-9]/', '', $phone);
            if ($clean !== '') {
                if (!str_starts_with($clean, '90')) {
                    $clean = '90' . ltrim($clean, '0');
                }
                $updates['whatsapp_number'] = $clean;
                if (empty($lead['whatsapp'])) {
                    $updates['whatsapp'] = $clean;
                }
            }
        }

        // Website & Instagram scrape
        $website = $details['websiteUri'] ?? null;
        if ($website) {
            $updates['website_url'] = $website;
            if (empty($lead['website'])) {
                $updates['website'] = $website;
            }
            $insta = $this->extract_instagram_from_website($website);
            if ($insta) {
                $updates['instagram_url'] = $insta;
                if (empty($lead['instagram'])) {
                    $updates['instagram'] = $insta;
                }
            }
        }

        // Rating & Reviews Count
        if (isset($details['rating'])) {
            $updates['rating'] = (float) $details['rating'];
        }
        if (isset($details['userRatingCount'])) {
            $updates['user_rating_count'] = (int) $details['userRatingCount'];
        }

        // Opening Hours
        $hours = $details['regularOpeningHours'] ?? ($details['currentOpeningHours'] ?? null);
        if ($hours) {
            $hours_json = json_encode($hours, JSON_UNESCAPED_UNICODE);
            $updates['opening_hours_json'] = $hours_json;
            $updates['opening_hours'] = $hours_json;
        }

        // Photos (first 3 references for proxy slider)
        if (!empty($details['photos']) && is_array($details['photos'])) {
            $photo_refs = [];
            foreach (array_slice($details['photos'], 0, 3) as $photo) {
                if (!empty($photo['name'])) {
                    $photo_refs[] = $photo['name'];
                }
            }
            if (!empty($photo_refs)) {
                $updates['photo_references'] = json_encode($photo_refs, JSON_UNESCAPED_UNICODE);
            }
            $updates['photos_json'] = json_encode(array_slice($details['photos'], 0, 5), JSON_UNESCAPED_UNICODE);
        }

        // Reviews
        if (!empty($details['reviews']) && is_array($details['reviews'])) {
            $updates['reviews_json'] = json_encode(array_slice($details['reviews'], 0, 5), JSON_UNESCAPED_UNICODE);
        }

        // Price Level
        if (isset($details['priceLevel'])) {
            $updates['price_level'] = $details['priceLevel'];
        }

        // Google Maps URI
        if (!empty($details['googleMapsUri']) && empty($lead['google_maps_uri'])) {
            $updates['google_maps_uri'] = $details['googleMapsUri'];
        }

        // Address & Coordinates
        if (!empty($details['formattedAddress']) && empty($lead['address'])) {
            $updates['address'] = $details['formattedAddress'];
        }
        if (!empty($details['location']['latitude']) && empty($lead['latitude'])) {
            $updates['latitude'] = (float) $details['location']['latitude'];
        }
        if (!empty($details['location']['longitude']) && empty($lead['longitude'])) {
            $updates['longitude'] = (float) $details['location']['longitude'];
        }

        // Parse address components (City, District, Neighborhood)
        if (!empty($details['addressComponents']) && is_array($details['addressComponents'])) {
            foreach ($details['addressComponents'] as $comp) {
                $types = $comp['types'] ?? [];
                $name = $comp['longText'] ?? ($comp['shortText'] ?? '');

                if (in_array('administrative_area_level_1', $types, true) && empty($lead['city'])) {
                    $updates['city'] = $name;
                } elseif (in_array('administrative_area_level_2', $types, true) && empty($lead['district'])) {
                    $updates['district'] = $name;
                } elseif ((in_array('sublocality_level_1', $types, true) || in_array('neighborhood', $types, true)) && empty($lead['neighborhood'])) {
                    $updates['neighborhood'] = $name;
                }
            }
        }

        return $updates;
    }

    /**
     * Try to extract Instagram URL from a website by fetching and regex scanning.
     */
    protected function extract_instagram_from_website(string $url): ?string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; BooKiBot/1.0; +https://randevuburada.kibusiness.co)',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $html = curl_exec($ch);
            $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200 || !$html) {
                return null;
            }

            if (preg_match('#https?://(?:www\.)?instagram\.com/([a-zA-Z0-9_.]+)/?#i', $html, $m)) {
                $handle = $m[1];
                $exclude = ['p', 'explore', 'accounts', 'about', 'developer', 'legal', 'reel', 'stories'];
                if (in_array(strtolower($handle), $exclude, true)) {
                    return null;
                }
                return 'https://www.instagram.com/' . $handle;
            }
        } catch (Throwable $e) {
            // Silently ignore website scrape errors
        }

        return null;
    }

    /**
     * Log Google Places API usage into places_api_usage table.
     */
    protected function log_api_usage(string $place_id, string $operation): void
    {
        if ($this->CI->db->table_exists('places_api_usage')) {
            try {
                $this->CI->db->insert('places_api_usage', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'endpoint' => 'places.googleapis.com/v1/places/{id}',
                    'operation' => $operation,
                    'place_id' => $place_id,
                    'http_status' => 200,
                    'sku_tier' => 'Enterprise',
                ]);
            } catch (Throwable $e) {}
        }
    }
}

