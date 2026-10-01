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
 * Consents controller.
 *
 * Handles user consent related operations.
 *
 * @package Controllers
 */
class Consents extends App_Controller
{
    /**
     * Consents constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('consents_model');
    }

    /**
     * Save consent record to the database.
     */
    public function save(): void
    {
        try {
            method('post');

            check('consent', 'array');

            $consent = request('consent');

            $consent['ip'] = $this->input->ip_address();

            $occurrences = $this->consents_model->get(['ip' => $consent['ip']], 1, 0, 'create_datetime DESC');

            if (!empty($occurrences)) {
                $last_consent = $occurrences[0];

                $last_consent_create_datetime_instance = new DateTime($last_consent['create_datetime']);

                $threshold_datetime_instance = new DateTime('-24 hours');

                if ($last_consent_create_datetime_instance > $threshold_datetime_instance) {
                    // Do not create a new consent.

                    json_response([
                        'success' => true,
                    ]);

                    return;
                }
            }

            $consent['id'] = $this->consents_model->save($consent);

            json_response([
                'success' => true,
                'id' => $consent['id'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Public page for customer to sign their appointment consent form.
     */
    public function sign(string $hash = ''): void
    {
        method('get');

        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('service_categories_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('digital_waivers_model');
        $this->load->library('legal_catalog');

        $appt = $this->appointments_model->query()->where('hash', $hash)->get()->row_array();
        if (!$appt) {
            show_error('Geçersiz veya süresi dolmuş onam formu bağlantısı.', 404);
            return;
        }

        $service = $this->services_model->find((int) $appt['id_services']);
        $customer = $this->customers_model->find((int) $appt['id_users_customer']);
        $provider = !empty($appt['id_users_provider']) ? $this->providers_model->find((int) $appt['id_users_provider']) : null;

        // Check if already signed
        $signatures = $this->digital_waivers_model->get_appointment_signatures((int) $appt['id']);
        if (!empty($signatures)) {
            $last_sig = end($signatures);
            $this->load->view('pages/consent_sign', [
                'page_title' => 'Onam Formu İmzalandı',
                'tenant_name' => setting('company_name') ?: 'BooKi',
                'already_signed' => true,
                'signer_name' => $last_sig['signer_full_name'],
                'signed_at' => $last_sig['signed_at'],
                'service_name' => $service['name'] ?? '',
            ]);
            return;
        }

        $service_category_name = '';
        if (!empty($service['id_service_categories'])) {
            $cat = $this->service_categories_model->find((int) $service['id_service_categories']);
            $service_category_name = $cat['name'] ?? '';
        }

        $start_dt = $appt['start_datetime'] ?? null;
        $appt_date = $start_dt ? date('d.m.Y H:i', strtotime($start_dt)) : date('d.m.Y H:i');

        $context = [
            'customer_full_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'customer_phone' => $customer['phone_number'] ?? '',
            'customer_email' => $customer['email'] ?? '',
            'service_name' => $service['name'] ?? 'Hizmet',
            'service_category' => $service_category_name,
            'service_price' => (float)($service['price'] ?? 0),
            'provider_name' => $provider ? trim($provider['first_name'] . ' ' . $provider['last_name']) : 'Merkez Uzmanı',
            'appointment_date' => $appt_date,
            'appointment_time' => date('H:i', strtotime($start_dt ?: 'now')),
            'appointment_datetime' => $appt_date,
            'tenant_name' => setting('company_name') ?: 'BooKi İşletmesi',
            'tenant_legal_name' => setting('company_name') ?: 'BooKi İşletmesi',
        ];

        // Fetch applicable waivers
        $all_waivers = $this->db->get('digital_waivers')->result_array();
        $consents = [];

        foreach ($all_waivers as $w) {
            $service_ids_str = (string) ($w['applicable_service_ids'] ?? '');
            $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));

            $is_applicable = in_array((string)$appt['id_services'], $service_ids, true);
            if (!$is_applicable && empty($service_ids_str) && str_contains(mb_strtolower($w['title'], 'UTF-8'), 'kvkk')) {
                $is_applicable = true;
            }

            if ($is_applicable) {
                $consents[] = [
                    'id' => (int) $w['id'],
                    'title' => $w['title'],
                    'is_mandatory' => (bool) $w['is_mandatory'],
                    'compiled_html' => $this->legal_catalog->compile($w['content_html'], $context),
                ];
            }
        }

        $this->load->view('pages/consent_sign', [
            'page_title' => 'Dijital Onam & Hizmet Sözleşmesi',
            'tenant_name' => setting('company_name') ?: 'BooKi',
            'already_signed' => false,
            'appointment_hash' => $hash,
            'customer_name' => $context['customer_full_name'],
            'service_name' => $service['name'] ?? '',
            'provider_name' => $context['provider_name'],
            'appointment_date' => $appt_date,
            'consents' => $consents,
        ]);
    }

    /**
     * Submit signature from the client public page.
     */
    public function submit_signature(string $hash = ''): void
    {
        try {
            method('post');

            $this->load->model('appointments_model');
            $this->load->model('digital_waivers_model');
            $this->load->model('customers_model');

            $appt = $this->appointments_model->query()->where('hash', $hash)->get()->row_array();
            if (!$appt) {
                throw new InvalidArgumentException('Randevu bulunamadı.');
            }

            $customer = $this->customers_model->find((int) $appt['id_users_customer']);
            $signer_name = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Danışan';

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $sig_data = $data['signature_data'] ?? 'data:text/plain;base64,' . base64_encode('CLIENT_ONLINE_ACCEPTED');

            // Find applicable waivers
            $all_waivers = $this->db->get('digital_waivers')->result_array();
            foreach ($all_waivers as $w) {
                $service_ids_str = (string) ($w['applicable_service_ids'] ?? '');
                $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));

                $is_applicable = in_array((string)$appt['id_services'], $service_ids, true);
                if (!$is_applicable && empty($service_ids_str) && str_contains(mb_strtolower($w['title'], 'UTF-8'), 'kvkk')) {
                    $is_applicable = true;
                }

                if ($is_applicable) {
                    $this->digital_waivers_model->sign_waiver([
                        'id_waivers' => (int) $w['id'],
                        'id_appointments' => (int) $appt['id'],
                        'id_users_customer' => (int) $appt['id_users_customer'],
                        'signer_full_name' => $signer_name,
                        'signer_email' => $customer['email'] ?? null,
                        'signer_phone' => $customer['phone_number'] ?? null,
                        'signature_data' => $sig_data,
                        'signature_type' => 'client_remote_canvas',
                        'compiled_content_html' => $w['content_html'],
                        'ip_address' => $this->input->ip_address(),
                    ]);
                }
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
