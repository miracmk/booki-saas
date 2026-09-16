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
 * Appointment Booking Service library.
 *
 * Encapsulates the core appointment booking logic used by both the standard
 * booking form and the AI Assistant. All appointment data provided by external
 * sources (LLM, API, etc.) is re-validated against the database before being
 * persisted.
 *
 * SECURITY: This service does NOT blindly trust any input. Every piece of data
 * is verified:
 * - Service IDs are checked against active services
 * - Provider IDs are verified to provide the selected service
 * - Date/time availability is re-calculated
 * - Station availability is re-checked (Salon Flora)
 * - Customer duplicate bookings are checked
 *
 * @package Libraries
 */
class Appointment_booking_service
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('services_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('stations_model');
        $this->CI->load->model('settings_model');
        $this->CI->load->model('consents_model');
        $this->CI->load->model('payment_settings_model');
        $this->CI->load->model('payment_transactions_model');
        $this->CI->load->library('availability');
        $this->CI->load->library('jitsi_client');
    }

    /**
     * Create an appointment with full validation.
     *
     * This method performs comprehensive validation of appointment data before
     * persisting it. All data is re-verified against the database, regardless
     * of the source (LLM, API, form, etc.).
     *
     * @param array $appointment Appointment data: [
     *   'id_services' => int,
     *   'id_users_provider' => int,
     *   'start_datetime' => 'Y-m-d H:i:s',
     *   ... other optional fields
     * ]
     * @param array $customer Customer data: [
     *   'first_name' => string,
     *   'last_name' => string,
     *   'email' => string,
     *   'phone_number' => string,
     *   ... other optional fields
     * ]
     *
     * @return array Response: [
     *   'success' => bool,
     *   'appointment_id' => ?int,
     *   'error' => ?string (error code if success=false),
     *   'message' => ?string (human-readable error)
     * ]
     */
    public function create(array $appointment, array $customer): array
    {
        try {
            // Step 1: Validate and fetch service
            if (empty($appointment['id_services'])) {
                return $this->error_response('invalid_service', 'Hizmet seçilmemiş.');
            }

            $service = $this->CI->services_model->find((int) $appointment['id_services']);

            // There is no "active" column on the services table - public bookability is expressed by
            // is_private (0 = publicly bookable), the same flag get_available_services() filters on.
            if (empty($service) || (bool) ($service['is_private'] ?? true)) {
                return $this->error_response('invalid_service', 'Seçilen hizmet kullanılamıyor.');
            }

            // Step 2: Validate and fetch provider
            if (empty($appointment['id_users_provider'])) {
                return $this->error_response('invalid_provider', 'Sağlayıcı seçilmemiş.');
            }

            $provider = $this->CI->providers_model->find((int) $appointment['id_users_provider']);

            if (empty($provider)) {
                return $this->error_response('invalid_provider', 'Seçilen sağlayıcı bulunamadı.');
            }

            // Step 3: Verify provider offers this service
            $available_providers = $this->CI->providers_model->get_available_providers();
            $provider_services = [];

            foreach ($available_providers as $available_provider) {
                if ((int) $available_provider['id'] === (int) $provider['id']) {
                    $provider_services = (array) ($available_provider['services'] ?? []);
                    break;
                }
            }

            if (!in_array((int) $appointment['id_services'], $provider_services, true)) {
                return $this->error_response('invalid_provider_service', 'Seçilen sağlayıcı bu hizmeti sunmamaktadır.');
            }

            // Step 4: Validate and re-check availability
            if (empty($appointment['start_datetime'])) {
                return $this->error_response('invalid_datetime', 'Randevu tarihi belirtilmemiş.');
            }

            // Parse date/time
            try {
                $appointment_start = new DateTime($appointment['start_datetime']);
                $date = $appointment_start->format('Y-m-d');
                $hour = $appointment_start->format('H:i');
            } catch (Exception $e) {
                return $this->error_response('invalid_datetime', 'Geçersiz tarih/saat formatı.');
            }

            // Re-check availability (CRITICAL: don't trust LLM's choice)
            $available_hours = $this->CI->availability->get_available_hours(
                $date,
                $service,
                $provider,
                null, // No exclusion for new appointments
            );

            if (!in_array($hour, $available_hours, true)) {
                return $this->error_response('unavailable_time', 'Seçilen saat müsait değil. Lütfen başka bir saat seçin.');
            }

            // Step 5: Validate customer data
            if (empty($customer['first_name']) || empty($customer['last_name'])) {
                return $this->error_response('invalid_customer', 'Ad ve soyadı bilgileri gerekli.');
            }

            if (empty($customer['email']) || !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->error_response('invalid_email', 'Geçersiz email adresi.');
            }

            if (empty($customer['phone_number'])) {
                return $this->error_response('invalid_phone', 'Telefon numarası gerekli.');
            }

            // Step 6: Check for duplicate customer appointments
            if ($this->CI->customers_model->exists($customer)) {
                // Reuse the existing customer record - save() validates email uniqueness BEFORE its own
                // exists() fallback runs, so unless the resolved id is set HERE it throws "email already
                // in use" for every returning customer (Booking.php, by contrast, pre-resolves the id the
                // same way before calling save()).
                $customer['id'] = $this->CI->customers_model->find_record_id($customer);

                $existing_appointments = $this->CI->appointments_model->get([
                    'id_users_customer' => $customer_id,
                    'start_datetime <=' => $appointment_start->format('Y-m-d H:i:s'),
                    'end_datetime >=' => $appointment_start->format('Y-m-d H:i:s'),
                ]);

                if (!empty($existing_appointments)) {
                    return $this->error_response('duplicate_booking', 'Bu saatte zaten bir randevunuz vardır.');
                }
            }

            // Step 7: Station availability (Salon Flora)
            $station_locks_held = [];
            $free_station_id = null;

            $provider_station_ids = $this->CI->providers_model->get_station_ids((int) $provider['id']);

            if (!empty($provider_station_ids)) {
                if (!$this->CI->stations_model->acquire_station_locks($provider_station_ids)) {
                    return $this->error_response('station_lock_failed', 'İstasyon kontrolü sırasında hata oluştu. Lütfen tekrar deneyin.');
                }

                $station_locks_held = $provider_station_ids;

                $appointment_end = clone $appointment_start;
                $appointment_end->modify('+' . (int) $service['duration'] . ' minutes');

                $free_station_id = $this->CI->stations_model->find_free_station(
                    $provider_station_ids,
                    $appointment_start->format('Y-m-d H:i:s'),
                    $appointment_end->format('Y-m-d H:i:s'),
                );

                if ($free_station_id === null) {
                    $this->CI->stations_model->release_station_locks($station_locks_held);

                    return $this->error_response('no_available_station', 'Bu saat için istasyon uygunluğu bulunmuyor.');
                }

                $appointment['id_stations'] = $free_station_id;
            }

            // Step 8: Set appointment defaults
            $appointment['color'] = $service['color'] ?? null;
            $appointment['is_unavailability'] = false;
            $appointment['location'] = $appointment['location'] ?? ($service['location'] ?? null);

            // Set appointment status
            $appointment_status_options_json = setting('appointment_status_options', '[]');
            $appointment_status_options = json_decode($appointment_status_options_json, true) ?? [];
            $appointment['status'] = $appointment_status_options[0] ?? null;

            // Calculate end datetime
            $appointment['end_datetime'] = $this->CI->appointments_model->calculate_end_datetime($appointment);

            // Jitsi integration
            if (setting('jitsi_enabled') === '1') {
                $appointment['meeting_link'] = $this->CI->jitsi_client->generate_link();
            }

            // Step 9: Save customer
            $customer['language'] = session('language') ?? config('language');

            $customer_id = $this->CI->customers_model->save($customer);
            $customer = $this->CI->customers_model->find($customer_id);

            if (empty($customer)) {
                if (!empty($station_locks_held)) {
                    $this->CI->stations_model->release_station_locks($station_locks_held);
                }

                return $this->error_response('customer_save_failed', 'Müşteri kaydı başarısız oldu.');
            }

            // Step 10: Save appointment
            $appointment['id_users_customer'] = $customer_id;

            $appointment_id = $this->CI->appointments_model->save($appointment);

            if (empty($appointment_id)) {
                if (!empty($station_locks_held)) {
                    $this->CI->stations_model->release_station_locks($station_locks_held);
                }

                return $this->error_response('appointment_save_failed', 'Randevu kaydı başarısız oldu.');
            }

            // Release station locks after successful save
            if (!empty($station_locks_held)) {
                $this->CI->stations_model->release_station_locks($station_locks_held);
            }

            // Step 11: Create payment intent if deposits are required (BooKi payment infrastructure)
            $payment_intent = null;

            try {
                $payment_settings = $this->CI->payment_settings_model->get_settings();

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
                                'appointment_id' => $appointment_id,
                                'customer_id' => $customer_id,
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
                        $transaction_id = $this->CI->payment_transactions_model->save([
                            'id_appointments' => $appointment_id,
                            'id_users' => $customer_id,
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
                log_message('error', 'Appointment_booking_service::create - payment intent creation failed: ' . $e->getMessage());

                // When a deposit is required, a booking that cannot initiate payment must NOT silently succeed -
                // the customer would walk away believing they are booked and the deposit was taken. Delete the
                // just-created appointment to free the slot and surface a clear error instead.
                if (!empty($appointment_id)) {
                    try {
                        $this->CI->appointments_model->delete((int) $appointment_id);
                    } catch (Throwable $delete_error) {
                        log_message(
                            'error',
                            'Appointment_booking_service::create - failed to delete appointment ' .
                                $appointment_id . ' after payment error: ' . $delete_error->getMessage(),
                        );
                    }
                }

                return $this->error_response(
                    'payment_init_failed',
                    'Ödeme başlatılamadı. Rezervasyonunuz oluşturulmadı. Lütfen tekrar deneyin.',
                );
            }

            $response = [
                'success' => true,
                'appointment_id' => $appointment_id,
            ];

            // Add payment info to response if payment was required
            if ($payment_intent !== null) {
                $response['payment_required'] = true;
                $response['payment_intent'] = $payment_intent;
            }

            return $response;
        } catch (Throwable $e) {
            log_message('error', 'Appointment_booking_service::create - ' . $e->getMessage());

            return $this->error_response('internal_error', 'İç hata oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }

    /**
     * Format an error response.
     *
     * @param string $error_code Machine-readable error code
     * @param string $message Human-readable error message
     *
     * @return array Error response
     */
    private function error_response(string $error_code, string $message): array
    {
        return [
            'success' => false,
            'error' => $error_code,
            'message' => $message,
        ];
    }
}
