<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Booking controller.
 *
 * Handles the booking related operations.
 *
 * Notice: This file used to have the booking page related code which since v1.5 has now moved to the Booking.php
 * controller for improved consistency.
 *
 * @package Controllers
 */
class Booking extends App_Controller
{
    public array $allowed_customer_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'timezone',
        'language',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
    ];
    public mixed $allowed_provider_fields = ['id', 'first_name', 'last_name', 'services', 'timezone'];
    public array $allowed_appointment_fields = [
        'id',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_link',
        'notes',
        'color',
        'status',
        'is_unavailability',
        'id_users_provider',
        'id_users_customer',
        'id_services',
        'id_stations', // Salon Flora customization
    ];

    /**
     * Booking constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('admins_model');
        $this->load->model('secretaries_model');
        $this->load->model('service_categories_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');
        $this->load->model('consents_model');
        $this->load->model('stations_model'); // Salon Flora customization
        $this->load->model('payment_settings_model'); // BooKi payment infrastructure
        $this->load->model('payment_transactions_model'); // BooKi payment infrastructure

        $this->load->library('timezones');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('availability');
        $this->load->library('webhooks_client');
        $this->load->library('jitsi_client');
    }

    /**
     * Verify CSRF token for booking submissions.
     *
     * @throws RuntimeException If CSRF token is invalid.
     */
    private function verify_csrf_token(): void
    {
        $csrf_token = request('csrf_token') ?? $this->input->get_request_header('X-CSRF');
        $csrf_cookie = $this->input->cookie('csrf_cookie');

        if (empty($csrf_token) || empty($csrf_cookie) || !hash_equals($csrf_cookie, $csrf_token)) {
            log_message('error', 'Invalid CSRF token in booking request from IP: ' . $this->input->ip_address());
            throw new RuntimeException('Security validation failed. Please refresh the page and try again.');
        }
    }

    /**
     * Render the booking page and display the selected appointment.
     *
     * This method will call the "index" callback to handle the page rendering.
     *
     * @param string $appointment_hash
     */
    public function reschedule(string $appointment_hash): void
    {
        html_vars(['appointment_hash' => $appointment_hash]);

        $this->index();
    }

    /**
     * Render the booking page.
    /**
     * Render the booking page.
     */
    public function index(): void
    {
        method('get');

        if (!is_app_installed()) {
            redirect('installation');

            return;
        }

        $company_name = setting('company_name');
        $company_logo = white_label_logo();
        $company_color = setting('company_color');
        $disable_booking = setting('disable_booking');
        $google_analytics_code = setting('google_analytics_code');
        $matomo_analytics_url = setting('matomo_analytics_url');
        $matomo_analytics_site_id = setting('matomo_analytics_site_id');

        if ($disable_booking) {
            $disable_booking_message = setting('disable_booking_message');

            html_vars([
                'show_message' => true,
                'page_title' => lang('page_title') . ' ' . e($company_name),
                'message_title' => lang('booking_is_disabled'),
                'message_text' => $disable_booking_message,
                'message_icon' => base_url('assets/img/error.png'),
                'google_analytics_code' => $google_analytics_code,
                'matomo_analytics_url' => $matomo_analytics_url,
                'matomo_analytics_site_id' => $matomo_analytics_site_id,
                'display_login_button' => setting('display_login_button'),
                'legal_notice_url' => setting('legal_notice_url'),
                'imprint_url' => setting('imprint_url'),
            ]);

            $this->load->view('pages/booking_message');

            return;
        }

        $available_services = $this->services_model->get_available_services(true);
        $available_providers = $this->providers_model->get_available_providers(true, null, true); // BooKi (2026-09-17) - new-booking candidates only, see is_active

        foreach ($available_providers as &$available_provider) {
            // Only expose the required provider data.

            $this->providers_model->only($available_provider, $this->allowed_provider_fields);
        }

        $date_format = setting('date_format');
        $time_format = setting('time_format');
        $first_weekday = setting('first_weekday');
        $display_first_name = setting('display_first_name');
        $require_first_name = setting('require_first_name');
        $display_last_name = setting('display_last_name');
        $require_last_name = setting('require_last_name');
        $display_email = setting('display_email');
        $require_email = setting('require_email');
        $display_phone_number = setting('display_phone_number');
        $require_phone_number = setting('require_phone_number');
        $display_address = setting('display_address');
        $require_address = setting('require_address');
        $display_city = setting('display_city');
        $require_city = setting('require_city');
        $display_zip_code = setting('display_zip_code');
        $require_zip_code = setting('require_zip_code');
        $display_notes = setting('display_notes');
        $require_notes = setting('require_notes');
        $ai_assistant_enabled = (bool) setting('ai_assistant_enabled');
        $display_cookie_notice = setting('display_cookie_notice');
        $cookie_notice_content = setting('cookie_notice_content');
        $display_terms_and_conditions = setting('display_terms_and_conditions');
        $terms_and_conditions_content = setting('terms_and_conditions_content');
        $display_privacy_policy = setting('display_privacy_policy');
        $privacy_policy_content = setting('privacy_policy_content');
        $display_any_provider = setting('display_any_provider');
        $display_login_button = setting('display_login_button');
        $display_delete_personal_information = setting('display_delete_personal_information');
        $book_advance_timeout = setting('book_advance_timeout');
        $legal_notice_url = setting('legal_notice_url');
        $imprint_url = setting('imprint_url');
        $theme = request('theme', setting('theme', 'default'));

        // Sanitize theme parameter to prevent directory traversal
        if (!empty($theme)) {
            // Only allow alphanumeric characters, underscores, and hyphens
            $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $theme);
        }

        if (empty($theme) || !file_exists(__DIR__ . '/../../assets/css/themes/' . $theme . '.min.css')) {
            $theme = 'default';
        }

        $timezones = $this->timezones->to_array();
        $grouped_timezones = $this->timezones->to_grouped_array();

        $appointment_hash = html_vars('appointment_hash');

        if (!empty($appointment_hash)) {
            // Load the appointments data and enable the manage mode of the booking page.

            $manage_mode = true;

            $results = $this->appointments_model->get(['hash' => $appointment_hash]);

            if (empty($results)) {
                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_not_found'),
                    'message_text' => lang('appointment_does_not_exist_in_db'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            $appointment = $results[0];
            $provider = $this->providers_model->find($appointment['id_users_provider']);

            // Make sure the appointment can still be rescheduled.

            $provider_timezone = new DateTimeZone($provider['timezone']);

            $appointment_start = new DateTime($appointment['start_datetime'], $provider_timezone);

            $limit = new DateTime('now', $provider_timezone);

            $limit->modify('+' . $book_advance_timeout . ' minutes');

            if ($appointment_start < $limit) {
                $hours = floor($book_advance_timeout / 60);

                $minutes = $book_advance_timeout % 60;

                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_locked'),
                    'message_text' => strtr(lang('appointment_locked_message'), [
                        '{$limit}' => sprintf('%02d:%02d', $hours, $minutes),
                    ]),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $this->customers_model->only($customer, $this->allowed_customer_fields);
            $customer_token = md5(uniqid(mt_rand(), true));

            // Cache the token for 10 minutes.
            $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600);
        } else {
            $manage_mode = false;
            $customer_token = false;
            $appointment = null;
            $provider = null;
            $customer = null;
        }

        script_vars([
            'manage_mode' => $manage_mode,
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_cookie_notice' => $display_cookie_notice,
            'display_any_provider' => setting('display_any_provider'),
            'future_booking_limit' => setting('future_booking_limit'),
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
            'customer_token' => $customer_token,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
        ]);

        $booking_json_ld = generate_schema_org_json_ld([
            'name' => $company_name ?: 'BooKi Online Randevu',
            'url' => base_url(),
            'booking_url' => base_url(),
            'image' => $company_logo ?: base_url('assets/img/logo.png'),
            'description' => setting('company_description') ?: ($company_name . ' online randevu ve rezervasyon sayfası.'),
            'telephone' => setting('company_phone') ?: null,
            'address' => [
                'streetAddress' => setting('company_address') ?: null,
                'addressLocality' => setting('company_district') ?: null,
                'addressRegion' => setting('company_city') ?: null,
                'addressCountry' => 'TR',
            ],
            'services' => $available_services,
        ]);

        html_vars([
            'booking_json_ld' => $booking_json_ld,
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'theme' => $theme,
            'company_name' => $company_name,
            'company_logo' => $company_logo,
            'company_color' => $company_color === '#ffffff' ? '' : $company_color,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_first_name' => $display_first_name,
            'require_first_name' => $require_first_name,
            'display_last_name' => $display_last_name,
            'require_last_name' => $require_last_name,
            'display_email' => $display_email,
            'require_email' => $require_email,
            'display_phone_number' => $display_phone_number,
            'require_phone_number' => $require_phone_number,
            'display_address' => $display_address,
            'require_address' => $require_address,
            'display_city' => $display_city,
            'require_city' => $require_city,
            'display_zip_code' => $display_zip_code,
            'require_zip_code' => $require_zip_code,
            'display_notes' => $display_notes,
            'require_notes' => $require_notes,
            'display_cookie_notice' => $display_cookie_notice,
            'cookie_notice_content' => $cookie_notice_content,
            'display_terms_and_conditions' => $display_terms_and_conditions,
            'terms_and_conditions_content' => $terms_and_conditions_content,
            'display_privacy_policy' => $display_privacy_policy,
            'privacy_policy_content' => $privacy_policy_content,
            'display_any_provider' => $display_any_provider,
            'display_login_button' => $display_login_button,
            'display_delete_personal_information' => $display_delete_personal_information,
            'legal_notice_url' => $legal_notice_url,
            'imprint_url' => $imprint_url,
            'google_analytics_code' => $google_analytics_code,
            'matomo_analytics_url' => $matomo_analytics_url,
            'matomo_analytics_site_id' => $matomo_analytics_site_id,
            'timezones' => $timezones,
            'grouped_timezones' => $grouped_timezones,
            'manage_mode' => $manage_mode,
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
            'ai_assistant_enabled' => $ai_assistant_enabled,
        ]);

        $this->load->view('pages/booking');
    }

    /**
     * Register the appointment to the database.
     */
    public function register(): void
    {
        try {
            method('post');

            // Verify CSRF token for booking submissions
            $this->verify_csrf_token();

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('post_data', 'array');
            check('captcha', 'string|null');

            $post_data = request('post_data');

            // Validate that post_data is an array
            if (!is_array($post_data)) {
                throw new InvalidArgumentException('Invalid request data format.');
            }

            $captcha = request('captcha');
            $appointment = $post_data['appointment'] ?? [];
            $customer = $post_data['customer'] ?? [];
            $manage_mode = filter_var($post_data['manage_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Validate required appointment fields
            if (empty($appointment) || !is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data.');
            }

            // IDOR Protection: If manage_mode is requested, verify the caller possesses the appointment hash
            if ($manage_mode) {
                $appointment_hash = trim((string) ($post_data['appointment_hash'] ?? ($appointment['hash'] ?? '')));
                if (empty($appointment_hash) || empty($appointment['id'])) {
                    throw new InvalidArgumentException('Geçersiz randevu güncelleme isteği.');
                }
                $target_appt = $this->appointments_model->find((int) $appointment['id']);
                if (!$target_appt || !hash_equals($target_appt['hash'], $appointment_hash)) {
                    throw new InvalidArgumentException('Randevu kimlik doğrulaması başarısız.');
                }
            } else {
                // Public booking must always insert a new appointment, never update an existing one
                unset($appointment['id']);
            }

            // Validate required customer fields
            if (empty($customer) || !is_array($customer)) {
                throw new InvalidArgumentException('Invalid customer data.');
            }

            // Sanitize and validate customer email
            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address format.');
            }

            // Sanitize customer fields - only allow expected fields
            $customer = array_intersect_key($customer, array_flip($this->allowed_customer_fields));

            // Sanitize appointment fields - only allow expected fields
            $appointment = array_intersect_key($appointment, array_flip($this->allowed_appointment_fields));

            if (!array_key_exists('address', $customer)) {
                $customer['address'] = '';
            }

            if (!array_key_exists('city', $customer)) {
                $customer['city'] = '';
            }

            // Salon Flora customization - "city" is a fixed Bursa-district dropdown on every form (public
            // booking + backend); reject anything that isn't one of those values rather than silently
            // accepting free text a form bypass (or a stale client) could otherwise submit.
            if ($customer['city'] !== '' && !in_array($customer['city'], SALONFLORA_BURSA_DISTRICTS, true)) {
                throw new InvalidArgumentException('Invalid city/district value.');
            }

            if (!array_key_exists('zip_code', $customer)) {
                $customer['zip_code'] = '';
            }

            if (!array_key_exists('notes', $customer)) {
                $customer['notes'] = '';
            }
            
            // Faz 43/44 Marketplace Attribution
            $ref_val = $this->input->post('ref') ?: (request('ref') ?: ($this->input->get('ref') ?: null));
            $is_marketplace = ($ref_val === 'marketplace' || (isset($_COOKIE['booki_marketplace_ref']) && $_COOKIE['booki_marketplace_ref'] === 'marketplace'));
            if ($is_marketplace) {
                $appointment['notes'] = "[Pazar Yeri] " . ($appointment['notes'] ?? '');
            }

            if (!array_key_exists('phone_number', $customer)) {
                $customer['phone_number'] = '';
            }

            // Check appointment availability before registering it to the database.
            $appointment['id_users_provider'] = $this->check_datetime_availability();

            if (!$appointment['id_users_provider']) {
                throw new RuntimeException(lang('requested_hour_is_unavailable'));
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $service = $this->services_model->find($appointment['id_services']);

            $require_captcha = (bool) setting('require_captcha');

            // Validate CAPTCHA or ALTCHA
            if ($require_captcha) {
                $altcha_enabled = setting('altcha_enabled') === '1';

                if ($altcha_enabled) {
                    // Validate ALTCHA
                    check('altcha_payload', 'string|null');
                    $altcha_payload = request('altcha_payload');

                    $this->load->library('altcha_client');

                    if (!$this->altcha_client->verify($altcha_payload)) {
                        json_response([
                            'altcha_verification' => false,
                        ]);
                        return;
                    }
                } else {
                    // Validate traditional CAPTCHA
                    $captcha_phrase = session('captcha_phrase');

                    if (strtoupper($captcha_phrase) !== strtoupper($captcha)) {
                        json_response([
                            'captcha_verification' => false,
                        ]);
                        return;
                    }
                }
            }

            if ($this->customers_model->exists($customer)) {
                $customer['id'] = $this->customers_model->find_record_id($customer);

                $existing_appointments = $this->appointments_model->get([
                    'id !=' => $manage_mode ? $appointment['id'] : null,
                    'id_users_customer' => $customer['id'],
                    'start_datetime <' => $appointment['end_datetime'],
                    'end_datetime >' => $appointment['start_datetime'],
                    'is_unavailability' => 0,
                ]);

                if (count($existing_appointments)) {
                    throw new RuntimeException(lang('customer_is_already_booked'));
                }
            }

            // Salon Flora customization: a provider may be assigned to more than one station - pick whichever of
            // their assigned stations is actually free during this period and record it on the appointment. This
            // also defends against two customers racing to book the last free station of a shared room: if every
            // assigned station is taken by the time we get here, the booking is rejected.
            $provider_station_ids = $this->providers_model->get_station_ids((int) $provider['id']);

            // Salon Flora customization - hold a named lock on every candidate station from the moment we check
            // freeness until the appointment claiming it is actually saved (released further below), so two
            // concurrent bookings can't both observe the same station as free and both save. See
            // Stations_model::acquire_station_locks() for why this is safe to leave unreleased on a crash.
            $station_locks_held = [];

            try {
                if (!empty($provider_station_ids)) {
                    if (!$this->stations_model->acquire_station_locks($provider_station_ids)) {
                        throw new RuntimeException(
                            'Bu saat için istasyon uygunluğu kontrol edilirken bir sorun oluştu. Lütfen tekrar deneyin.',
                        );
                    }

                    $station_locks_held = $provider_station_ids;

                    $free_station_id = $this->stations_model->find_free_station(
                        $provider_station_ids,
                        $appointment['start_datetime'],
                        $appointment['end_datetime'],
                        $manage_mode ? (int) $appointment['id'] : null,
                    );

                    if ($free_station_id === null) {
                        throw new RuntimeException(
                            'Bu saat için seçilen istasyon başka bir randevu tarafından kullanılıyor. Lütfen başka bir saat seçin.',
                        );
                    }

                    $appointment['id_stations'] = $free_station_id;
                }

                // Jitsi integration: if enabled, generate a Jitsi meeting link for the appointment
                if (setting('jitsi_enabled') === '1') {
                    $appointment['meeting_link'] = $this->jitsi_client->generate_link();
                }

                if (empty($appointment['location']) && !empty($service['location'])) {
                    $appointment['location'] = $service['location'];
                }

                if (empty($appointment['color']) && !empty($service['color'])) {
                    $appointment['color'] = $service['color'];
                }

                $customer_ip = $this->input->ip_address();

                // Create the consents (if needed).
                $consent = [
                    'first_name' => $customer['first_name'] ?? '-',
                    'last_name' => $customer['last_name'] ?? '-',
                    'email' => $customer['email'] ?? '-',
                    'ip' => $customer_ip,
                ];

                if (setting('display_terms_and_conditions')) {
                    $consent['type'] = 'terms-and-conditions';

                    $this->consents_model->save($consent);
                }

                if (setting('display_privacy_policy')) {
                    $consent['type'] = 'privacy-policy';

                    $this->consents_model->save($consent);
                }

                // Save customer language (the language which is used to render the booking page).
                $customer['language'] = session('language') ?? config('language');

                $this->customers_model->only($customer, $this->allowed_customer_fields);

                $customer_id = $this->customers_model->save($customer);
                $customer = $this->customers_model->find($customer_id);

                $appointment['id_users_customer'] = $customer_id;
                $appointment['is_unavailability'] = false;
                $appointment['color'] = $service['color'];

                $appointment_status_options_json = setting('appointment_status_options', '[]');
                $appointment_status_options = json_decode($appointment_status_options_json, true) ?? [];
                $appointment['status'] = $appointment_status_options[0] ?? null;
                $appointment['end_datetime'] = $this->appointments_model->calculate_end_datetime($appointment);

                // Enforce provider conflict check to prevent concurrent double-booking
                if ($this->appointments_model->has_provider_conflict((int) $provider['id'], $appointment['start_datetime'], $appointment['end_datetime'], $manage_mode ? (int) ($appointment['id'] ?? null) : null)) {
                    throw new RuntimeException(lang('requested_hour_is_unavailable'));
                }

                $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

                $appointment_id = $this->appointments_model->save($appointment);
            } finally {
                if (!empty($station_locks_held)) {
                    $this->stations_model->release_station_locks($station_locks_held);
                }
            }

            $appointment = $this->appointments_model->find($appointment_id);

            // Mark waitlist converted if applicable
            try {
                if (!empty($customer['id'])) {
                    $this->load->model('waitlist_model');
                    $matching_waitlists = $this->waitlist_model->get([
                        'id_users_customer' => (int) $customer['id'],
                        'id_services' => (int) $service['id'],
                    ]);
                    foreach ($matching_waitlists as $wl_entry) {
                        if (in_array($wl_entry['status'], ['waiting', 'notified'], true)) {
                            $this->waitlist_model->mark_converted((int)$wl_entry['id'], (int)$appointment_id);
                        }
                    }
                }
            } catch (Throwable $e) {
                log_message('error', 'Failed to mark waitlist converted in Booking.php: ' . $e->getMessage());
            }

            // Attribute booking conversion to marketing ad / session attribution
            try {
                $this->load->model('traffic_attributions_model');
                $sessionId = $this->input->cookie('booki_session_id') ?: (request('session_id') ?? session_id());
                $servicePrice = (float) ($service['price'] ?? 0);
                $this->traffic_attributions_model->attribute_conversion(
                    (string) $sessionId,
                    (int) $customer_id,
                    (int) $appointment_id,
                    $servicePrice,
                    $customer,
                    $customer_ip
                );
            } catch (Throwable $attrEx) {
                log_message('error', 'Attribution recording failed: ' . $attrEx->getMessage());
            }

            // BooKi payment infrastructure - create payment intent if deposits are required and a gateway is active
            $payment_intent = null;

            try {
                $payment_settings = $this->payment_settings_model->get_settings();

                if ($payment_settings['require_deposit'] && $payment_settings['active_gateway'] !== 'none') {
                    $payment_gateway = Payment_gateway_factory::make($payment_settings);

                    if ($payment_gateway !== null) {
                        // Calculate deposit amount
                        $deposit_amount = (float) ($service['price'] ?? 0);

                        if ($payment_settings['deposit_type'] === 'percentage') {
                            $deposit_amount = $deposit_amount * ($payment_settings['deposit_value'] / 100);
                        } else {
                            $deposit_amount = (float) $payment_settings['deposit_value'];
                        }

                        // Create payment intent
                        $intent_response = $payment_gateway->create_payment_intent(
                            $deposit_amount,
                            'TRY',
                            [
                                'appointment_id' => $appointment['id'],
                                'customer_id' => $customer['id'],
                                'customer_name' => $customer['first_name'] ?? '',
                                'customer_surname' => $customer['last_name'] ?? '',
                                'customer_email' => $customer['email'] ?? '',
                                'customer_phone' => $customer['phone_number'] ?? '',
                                'customer_address' => $customer['address'] ?? '',
                                'customer_city' => $customer['city'] ?? '',
                                'customer_zip' => $customer['zip_code'] ?? '',
                            ],
                        );

                        // Save transaction record
                        $transaction_id = $this->payment_transactions_model->save([
                            'id_appointments' => $appointment['id'],
                            'id_users' => $customer['id'],
                            'gateway' => $payment_settings['active_gateway'],
                            'intent_id' => $intent_response['intent_id'] ?? null,
                            'amount' => $deposit_amount,
                            'currency' => 'TRY',
                            'status' => 'pending',
                            'type' => 'deposit',
                            'raw_response' => $intent_response['raw_response'] ?? null,
                        ]);

                        $payment_intent = [
                            'transaction_id' => $transaction_id,
                            'intent_id' => $intent_response['intent_id'] ?? null,
                            'amount' => $deposit_amount,
                            'gateway' => $payment_settings['active_gateway'],
                            'checkout_form' => $intent_response['checkout_form'] ?? null,
                        ];
                    }
                }
            } catch (Throwable $e) {
                log_message('error', 'Booking::register - payment intent creation failed: ' . $e->getMessage());

                // BooKi bugfix - when a deposit is required, an unpaid booking must not silently succeed
                // (the customer would believe they are booked and their deposit was collected). Delete the
                // just-created appointment so the slot is freed, then fail loudly.
                if (!empty($appointment_id)) {
                    try {
                        $this->appointments_model->delete((int) $appointment_id);
                    } catch (Throwable $delete_error) {
                        log_message(
                            'error',
                            'Booking::register - failed to delete appointment ' . $appointment_id .
                                ' after payment error: ' . $delete_error->getMessage(),
                        );
                    }
                }

                throw new RuntimeException(
                    'Ödeme başlatılamadı. Rezervasyonunuz gerçekleştirilmedi. Lütfen tekrar deneyin.',
                );
            }

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

            $this->notifications->notify_appointment_saved(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $manage_mode,
            );

            // BooKi (Dalga 3 / Faz 3.1) - Communication Hub: appointment_created event.
            // Best-effort by contract - publish() logs, never throws.
            $this->load->library('communication_hub');
            $this->communication_hub->publish(
                'appointment_created',
                compact('appointment', 'service', 'provider', 'customer', 'settings'),
            );

            // BooKi (Dalga 3 / Faz 3.2) - Automation Engine: same event.
            $this->load->library('automation_engine');
            $this->automation_engine->evaluate(
                'appointment_created',
                compact('appointment', 'service', 'provider', 'customer', 'settings'),
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

            $response = [
                'appointment_id' => $appointment['id'],
                'appointment_hash' => $appointment['hash'],
            ];

            // Add payment info to response if payment was required
            if ($payment_intent !== null) {
                $response['payment_required'] = true;
                $response['payment_intent'] = $payment_intent;
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It is possible that two or more customers select the same appointment date and time concurrently. The app won't
     * allow this to happen, so one of the two will eventually get the selected date and the other one will have
     * to choose for another one.
     *
     * Use this method just before the customer confirms the appointment registration. If the selected date was reserved
     * in the meanwhile, the customer must be prompted to select another time.
     *
     * @return int|null Returns the ID of the provider that is available for the appointment.
     *
     * @throws Exception
     */
    protected function check_datetime_availability(): ?int
    {
        $post_data = request('post_data');

        $appointment = $post_data['appointment'];

        $appointment_start = new DateTime($appointment['start_datetime']);

        $date = $appointment_start->format('Y-m-d');

        $hour = $appointment_start->format('H:i');

        if ($appointment['id_users_provider'] === ANY_PROVIDER) {
            $appointment['id_users_provider'] = $this->search_any_provider($appointment['id_services'], $date, $hour);

            return $appointment['id_users_provider'];
        }

        $service = $this->services_model->find($appointment['id_services']);

        $exclude_appointment_id = $appointment['id'] ?? null;

        $provider = $this->providers_model->find($appointment['id_users_provider']);

        $available_hours = $this->availability->get_available_hours(
            $date,
            $service,
            $provider,
            $exclude_appointment_id,
        );

        $is_still_available = false;

        $appointment_hour = date('H:i', strtotime($appointment['start_datetime']));

        foreach ($available_hours as $available_hour) {
            if ($appointment_hour === $available_hour) {
                $is_still_available = true;
                break;
            }
        }

        return $is_still_available ? $appointment['id_users_provider'] : null;
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param int $service_id Service ID
     * @param string $date Selected date (Y-m-d).
     * @param string|null $hour Selected hour (H:i).
     *
     * @return int|null Returns the ID of the provider that can provide the service at the selected date.
     *
     * @throws Exception
     */
    protected function search_any_provider(int $service_id, string $date, ?string $hour = null): ?int
    {
        $available_providers = $this->providers_model->get_available_providers(true, null, true); // BooKi (2026-09-17) - new-booking candidates only, see is_active

        $service = $this->services_model->find($service_id);

        $provider_id = null;

        $max_hours_count = 0;

        foreach ($available_providers as $provider) {
            foreach ($provider['services'] as $provider_service_id) {
                if ($provider_service_id == $service_id) {
                    // Check if the provider is available for the requested date.
                    $available_hours = $this->availability->get_available_hours($date, $service, $provider);

                    if (
                        count($available_hours) > $max_hours_count &&
                        (empty($hour) || in_array($hour, $available_hours))
                    ) {
                        $provider_id = $provider['id'];

                        $max_hours_count = count($available_hours);
                    }
                }
            }
        }

        return $provider_id;
    }

    /**
     * Get the available appointment hours for the selected date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     */
    public function get_available_hours(): void
    {
        try {
            method('post');

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('selected_date', 'date');
            check('manage_mode', 'bool|null');
            check('appointment_id', 'numeric|null');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $selected_date = request('selected_date');

            // Do not continue if there was no provider selected (more likely there is no provider in the system).

            if (empty($provider_id)) {
                json_response();

                return;
            }

            // If manage mode is TRUE then the following we should not consider the selected appointment when
            // calculating the available time periods of the provider.

            $exclude_appointment_id = request('manage_mode') ? request('appointment_id') : null;

            // If the user has selected the "any-provider" option then we will need to search for an available provider
            // that will provide the requested service.

            $service = $this->services_model->find($service_id);

            if ($provider_id === ANY_PROVIDER) {
                $providers = $this->providers_model->get_available_providers(true, null, true); // BooKi (2026-09-17) - new-booking candidates only, see is_active

                $available_hours = [];

                foreach ($providers as $provider) {
                    if (!in_array($service_id, $provider['services'])) {
                        continue;
                    }

                    $provider_available_hours = $this->availability->get_available_hours(
                        $selected_date,
                        $service,
                        $provider,
                        $exclude_appointment_id,
                    );

                    $available_hours = array_merge($available_hours, $provider_available_hours);
                }

                $available_hours = array_unique(array_values($available_hours));

                sort($available_hours);

                $response = $available_hours;
            } else {
                $provider = $this->providers_model->find($provider_id);

                $response = $this->availability->get_available_hours(
                    $selected_date,
                    $service,
                    $provider,
                    $exclude_appointment_id,
                );
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get the available appointment dates for the selected date period.
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the "Y-m-d" format.
     *
     * Outputs a JSON string with the unavailability dates. that are unavailability.
     */
    public function get_unavailable_dates(): void
    {
        try {
            method('get');

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('appointment_id', 'numeric|null');
            check('manage_mode', 'bool|null');
            check('selected_date', 'date');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $appointment_id = request('appointment_id');
            $manage_mode = filter_var(request('manage_mode'), FILTER_VALIDATE_BOOLEAN);
            $selected_date_string = request('selected_date');
            $selected_date = new DateTime($selected_date_string);
            $number_of_days_in_month = (int) $selected_date->format('t');
            $unavailable_dates = [];

            $provider_ids =
                $provider_id === ANY_PROVIDER ? $this->search_providers_by_service($service_id) : [$provider_id];

            $exclude_appointment_id = $manage_mode ? $appointment_id : null;

            // Get the service record.
            $service = $this->services_model->find($service_id);

            for ($i = 1; $i <= $number_of_days_in_month; $i++) {
                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);

                if ($current_date < new DateTime(date('Y-m-d 00:00:00'))) {
                    // Past dates become immediately unavailability.
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                    continue;
                }

                // Finding at least one slot of availability.
                foreach ($provider_ids as $current_provider_id) {
                    $provider = $this->providers_model->find($current_provider_id);

                    $available_hours = $this->availability->get_available_hours(
                        $current_date->format('Y-m-d'),
                        $service,
                        $provider,
                        $exclude_appointment_id,
                    );

                    if (!empty($available_hours)) {
                        break;
                    }
                }

                // No availability amongst all the provider.
                if (empty($available_hours)) {
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                }
            }

            if (count($unavailable_dates) === $number_of_days_in_month) {
                json_response([
                    'is_month_unavailable' => true,
                ]);

                return;
            }

            json_response($unavailable_dates);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param int $service_id The requested service ID.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    /**
     * Register a series of recurring appointments (BooKi, Dalga 1).
     *
     * This is a separate entry point from register() - the single-appointment
     * booking flow above is completely untouched by this method. Each
     * occurrence is booked through Recurrence_service, which re-validates
     * availability/station allocation per occurrence via
     * Appointment_booking_service::create() - a conflicting future date is
     * skipped and reported, never silently forced.
     */
    public function create_recurring_series(): void
    {
        try {
            method('post');

            $this->verify_csrf_token();

            if (setting('disable_booking')) {
                abort(403);
            }

            check('post_data', 'array');

            $post_data = request('post_data');

            if (!is_array($post_data)) {
                throw new InvalidArgumentException('Invalid request data format.');
            }

            $appointment = $post_data['appointment'] ?? [];
            $customer = $post_data['customer'] ?? [];
            $frequency = (string) ($post_data['frequency'] ?? 'weekly');
            $occurrences_total = (int) ($post_data['occurrences_total'] ?? 0);
            $interval_count = (int) ($post_data['interval_count'] ?? 1);

            if (empty($appointment) || !is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data.');
            }

            if (empty($customer) || !is_array($customer)) {
                throw new InvalidArgumentException('Invalid customer data.');
            }

            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address format.');
            }

            $customer = array_intersect_key($customer, array_flip($this->allowed_customer_fields));
            $appointment = array_intersect_key($appointment, array_flip($this->allowed_appointment_fields));

            foreach (['address', 'city', 'zip_code', 'notes', 'phone_number'] as $optional_field) {
                if (!array_key_exists($optional_field, $customer)) {
                    $customer[$optional_field] = '';
                }
            }

            $this->load->library('recurrence_service');

            $result = $this->recurrence_service->create_series(
                $appointment,
                $customer,
                $frequency,
                $occurrences_total,
                $interval_count,
            );

            json_response([
                'success' => true,
                'group_id' => $result['group_id'],
                'created_appointment_ids' => $result['created_appointment_ids'],
                'skipped' => $result['skipped'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    protected function search_providers_by_service(int $service_id): array
    {
        $available_providers = $this->providers_model->get_available_providers(true, null, true); // BooKi (2026-09-17) - new-booking candidates only, see is_active
        $provider_list = [];

        foreach ($available_providers as $provider) {
            foreach ($provider['services'] as $provider_service_id) {
                if ($provider_service_id === $service_id) {
                    // Check if the provider is affected to the selected service.
                    $provider_list[] = $provider['id'];
                }
            }
        }

        return $provider_list;
    }
}
