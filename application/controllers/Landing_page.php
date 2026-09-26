<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Public Marketing Landing Page Controller
 *
 * Serves tenant landing pages configured under Marketing, tracks visits,
 * records ad parameters (UTM, gclid, fbclid), and forwards to booking.
 * ---------------------------------------------------------------------------- */

class Landing_page extends App_Controller
{
    /**
     * View a specific landing page by slug.
     */
    public function view(string $slug = ''): void
    {
        method('get');

        if (empty($slug)) {
            abort(404, 'Sayfa belirtilmedi.');
        }

        $this->load->model('landing_pages_model');
        $this->load->model('traffic_attributions_model');
        $this->load->model('settings_model');

        $page = $this->landing_pages_model->find_by_slug($slug);

        if (!$page) {
            abort(404, 'Açılış sayfası bulunamadı.');
        }

        // Increment view count
        $this->landing_pages_model->increment_views((int) $page['id']);

        // Manage session tracking
        $sessionId = $this->input->cookie('booki_session_id');
        if (empty($sessionId)) {
            $sessionId = 'bks_' . bin2hex(random_bytes(10));
            $this->input->set_cookie([
                'name' => 'booki_session_id',
                'value' => $sessionId,
                'expire' => 30 * 86400,
                'path' => '/',
            ]);
        }

        // Capture ad click attribution parameters
        $utmSource = $this->input->get('utm_source');
        $utmMedium = $this->input->get('utm_medium');
        $utmCampaign = $this->input->get('utm_campaign');
        $utmTerm = $this->input->get('utm_term');
        $utmContent = $this->input->get('utm_content');
        $gclid = $this->input->get('gclid');
        $fbclid = $this->input->get('fbclid');

        $this->traffic_attributions_model->record_visit([
            'session_id' => $sessionId,
            'landing_page_slug' => $slug,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm,
            'utm_content' => $utmContent,
            'gclid' => $gclid,
            'fbclid' => $fbclid,
            'referrer' => $this->input->server('HTTP_REFERER'),
            'user_agent' => $this->input->user_agent(),
            'ip_address' => $this->input->ip_address(),
            'click_timestamp' => date('Y-m-d H:i:s'),
        ]);

        $settings = $this->settings_model->get();

        // Build target booking link with attribution preserved
        $bookingQuery = [
            'session_id' => $sessionId,
        ];
        if (!empty($page['id_services'])) {
            $bookingQuery['service'] = $page['id_services'];
        }
        if (!empty($utmSource)) {
            $bookingQuery['utm_source'] = $utmSource;
        }
        if (!empty($utmCampaign)) {
            $bookingQuery['utm_campaign'] = $utmCampaign;
        }
        if (!empty($gclid)) {
            $bookingQuery['gclid'] = $gclid;
        }
        if (!empty($fbclid)) {
            $bookingQuery['fbclid'] = $fbclid;
        }

        $bookingUrl = site_url('booking') . '?' . http_build_query($bookingQuery);

        $this->load->view('pages/landing_page_view', [
            'page' => $page,
            'company_name' => $settings['company_name'] ?? 'BooKi',
            'currency' => $settings['currency'] ?? '₺',
            'booking_url' => $bookingUrl,
            'google_analytics_code' => $settings['google_analytics_id'] ?? ($settings['google_analytics_code'] ?? null),
            'meta_pixel_id' => $settings['meta_pixel_id'] ?? null,
        ]);
    }
}
