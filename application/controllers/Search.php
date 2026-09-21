<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Global Command Center & Omnisearch Controller (Cmd+K / Ctrl+K)
 * ---------------------------------------------------------------------------- */

class Search extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!session('user_id')) {
            if ($this->input->is_ajax_request() || $this->input->get('q') !== null) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']))
                    ->_display();
                exit;
            }
            redirect('login');
            exit;
        }

        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('products_model');
    }

    /**
     * Index alias for search.
     */
    public function index(): void
    {
        $this->global_query();
    }

    /**
     * Omnisearch alias for search.
     */
    public function omnisearch(): void
    {
        $this->global_query();
    }

    /**
     * Unified Global Omnisearch endpoint.
     */
    public function global_query(): void
    {
        $q = trim((string) $this->input->get('q'));

        if (mb_strlen($q) < 2) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => true, 'results' => []]));
            return;
        }

        $results = [];

        // 1. Customers
        $customers = $this->db
            ->select('id, first_name, last_name, phone_number, email')
            ->from('users')
            ->where('id_roles', 3) // Customer role
            ->group_start()
            ->like('first_name', $q)
            ->or_like('last_name', $q)
            ->or_like('phone_number', $q)
            ->or_like('email', $q)
            ->group_end()
            ->limit(5)
            ->get()
            ->result_array();

        foreach ($customers as $c) {
            $results[] = [
                'category' => 'Müşteriler',
                'id' => $c['id'],
                'title' => $c['first_name'] . ' ' . $c['last_name'],
                'subtitle' => $c['phone_number'] ?: $c['email'],
                'url' => site_url('customers?id=' . $c['id']),
                'icon' => 'fa-user',
                'badge' => 'primary',
            ];
        }

        // 2. Appointments
        $appts = $this->db
            ->select('a.id, a.start_datetime, s.name as service_name, c.first_name, c.last_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->group_start()
            ->like('s.name', $q)
            ->or_like('c.first_name', $q)
            ->or_like('c.last_name', $q)
            ->or_like('a.id', $q)
            ->group_end()
            ->limit(5)
            ->get()
            ->result_array();

        foreach ($appts as $a) {
            $results[] = [
                'category' => 'Randevular',
                'id' => $a['id'],
                'title' => '#' . $a['id'] . ' ' . ($a['service_name'] ?: 'Randevu') . ' - ' . $a['first_name'] . ' ' . $a['last_name'],
                'subtitle' => date('d.m.Y H:i', strtotime($a['start_datetime'])),
                'url' => site_url('calendar?appointment_id=' . $a['id']),
                'icon' => 'fa-calendar-check',
                'badge' => 'info',
            ];
        }

        // 3. Adisyons
        if ($this->db->table_exists('adisyons')) {
            $adisyons = $this->db
                ->select('ad.id, ad.adisyon_number, ad.total_amount, ad.payment_status, c.first_name, c.last_name')
                ->from('adisyons ad')
                ->join('users c', 'c.id = ad.id_users_customer', 'left')
                ->group_start()
                ->like('ad.adisyon_number', $q)
                ->or_like('c.first_name', $q)
                ->or_like('c.last_name', $q)
                ->group_end()
                ->limit(5)
                ->get()
                ->result_array();

            foreach ($adisyons as $ad) {
                $results[] = [
                    'category' => 'Adisyonlar',
                    'id' => $ad['id'],
                    'title' => 'Adisyon ' . $ad['adisyon_number'] . ' (' . $ad['total_amount'] . ' ₺)',
                    'subtitle' => ($ad['first_name'] ? $ad['first_name'] . ' ' . $ad['last_name'] . ' | ' : '') . 'Durum: ' . $ad['payment_status'],
                    'url' => site_url('adisyons?id=' . $ad['id']),
                    'icon' => 'fa-receipt',
                    'badge' => 'warning',
                ];
            }
        }

        // 4. Invoices
        if ($this->db->table_exists('invoices')) {
            $invoices = $this->db
                ->select('inv.id, inv.invoice_number, inv.total, inv.status, c.first_name, c.last_name')
                ->from('invoices inv')
                ->join('users c', 'c.id = inv.id_users_customer', 'left')
                ->group_start()
                ->like('inv.invoice_number', $q)
                ->or_like('c.first_name', $q)
                ->or_like('c.last_name', $q)
                ->group_end()
                ->limit(5)
                ->get()
                ->result_array();

            foreach ($invoices as $inv) {
                $results[] = [
                    'category' => 'Faturalar',
                    'id' => $inv['id'],
                    'title' => 'Fatura ' . $inv['invoice_number'] . ' (' . $inv['total'] . ' ₺)',
                    'subtitle' => ($inv['first_name'] ? $inv['first_name'] . ' ' . $inv['last_name'] . ' | ' : '') . 'Durum: ' . $inv['status'],
                    'url' => site_url('invoices?id=' . $inv['id']),
                    'icon' => 'fa-file-invoice-dollar',
                    'badge' => 'secondary',
                ];
            }
        }

        // 5. Products / Stock
        if ($this->db->table_exists('products')) {
            $products = $this->db
                ->select('id, name, sku, sale_price, stock_quantity')
                ->from('products')
                ->group_start()
                ->like('name', $q)
                ->or_like('sku', $q)
                ->group_end()
                ->limit(5)
                ->get()
                ->result_array();

            foreach ($products as $p) {
                $results[] = [
                    'category' => 'Ürünler & Stok',
                    'id' => $p['id'],
                    'title' => $p['name'] . ($p['sku'] ? ' (' . $p['sku'] . ')' : ''),
                    'subtitle' => 'Fiyat: ' . $p['sale_price'] . ' ₺ | Stok: ' . $p['stock_quantity'],
                    'url' => site_url('pos?product_id=' . $p['id']),
                    'icon' => 'fa-boxes',
                    'badge' => 'success',
                ];
            }
        }

        // 6. Restaurant Tables
        if ($this->db->table_exists('restaurant_tables')) {
            $tables = $this->db
                ->select('id, table_number, name, section, status')
                ->from('restaurant_tables')
                ->group_start()
                ->like('table_number', $q)
                ->or_like('name', $q)
                ->or_like('section', $q)
                ->group_end()
                ->limit(5)
                ->get()
                ->result_array();

            foreach ($tables as $t) {
                $results[] = [
                    'category' => 'Masalar',
                    'id' => $t['id'],
                    'title' => 'Masa ' . $t['table_number'] . ' (' . $t['section'] . ')',
                    'subtitle' => 'Durum: ' . $t['status'],
                    'url' => site_url('restaurant?table_id=' . $t['id']),
                    'icon' => 'fa-chair',
                    'badge' => 'dark',
                ];
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'results' => $results]));
    }
}
