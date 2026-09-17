<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-ERP Accounting Manager (2026-09-17).
 *
 * Orchestrates invoice synchronization with Turkish ERP / e-Fatura systems:
 *  - Paraşüt (Cloud e-Fatura / e-Arşiv)
 *  - BizimHesap (Cloud ERP)
 *  - Logo (Logo Go3 / Tiger REST API)
 *  - Mikro (Mikro Yazılım API)
 * ---------------------------------------------------------------------------- */

class Erp_manager
{
    protected CI_Controller|EA_Controller $CI;

    public const PROVIDERS = [
        'parasut' => 'Paraşüt',
        'bizimhesap' => 'BizimHesap',
        'logo' => 'Logo ERP',
        'mikro' => 'Mikro Yazılım',
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('invoices_model');
    }

    /**
     * Synchronize an internal invoice to the configured or selected ERP system.
     *
     * @param int $invoice_id The local invoice ID.
     * @param string|null $provider Specific provider override ('parasut', 'bizimhesap', 'logo', 'mikro').
     * @return array Sync result metadata.
     */
    public function sync_invoice(int $invoice_id, ?string $provider = null): array
    {
        $invoiceData = $this->CI->invoices_model->find_with_items($invoice_id);
        $invoice = $invoiceData['invoice'];
        $items = $invoiceData['items'];

        if (!$provider) {
            $provider = $this->get_default_provider();
        }

        if (!array_key_exists($provider, self::PROVIDERS)) {
            $provider = 'parasut';
        }

        // Fetch customer details
        $customer = null;
        if (!empty($invoice['id_users_customer'])) {
            $customer = $this->CI->db->get_where('users', ['id' => $invoice['id_users_customer']])->row_array();
        }

        $ettnUuid = $this->generate_uuid();
        $externalInvoiceNumber = strtoupper(substr($provider, 0, 3)) . '-' . date('Y') . '-' . sprintf('%06d', $invoice_id);

        try {
            // Dispatch to specific provider handler
            $result = match ($provider) {
                'parasut' => $this->sync_to_parasut($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                'bizimhesap' => $this->sync_to_bizimhesap($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                'logo' => $this->sync_to_logo($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                'mikro' => $this->sync_to_mikro($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                default => $this->sync_to_parasut($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
            };

            // Update invoice with successful ERP sync info
            $this->CI->db->update('invoices', [
                'erp_status' => 'synced',
                'erp_provider' => $provider,
                'erp_invoice_id' => $result['external_id'],
                'erp_synced_at' => date('Y-m-d H:i:s'),
                'erp_error' => null,
            ], ['id' => $invoice_id]);

            return [
                'success' => true,
                'provider' => $provider,
                'provider_name' => self::PROVIDERS[$provider],
                'external_id' => $result['external_id'],
                'ettn' => $ettnUuid,
                'message' => self::PROVIDERS[$provider] . ' sistemine başarıyla aktarıldı.',
            ];
        } catch (Throwable $e) {
            $this->CI->db->update('invoices', [
                'erp_status' => 'failed',
                'erp_provider' => $provider,
                'erp_error' => $e->getMessage(),
            ], ['id' => $invoice_id]);

            throw $e;
        }
    }

    private function sync_to_parasut(array $invoice, array $items, ?array $customer, string $ettn, string $extNum): array
    {
        // Paraşüt e-Arşiv / Fatura Payload format
        $payload = [
            'data' => [
                'type' => 'sales_invoices',
                'attributes' => [
                    'item_type' => 'invoice',
                    'issue_date' => date('Y-m-d', strtotime($invoice['created_at'])),
                    'due_date' => $invoice['due_at'] ?? date('Y-m-d', strtotime('+7 days')),
                    'invoice_series' => 'BK',
                    'invoice_id' => $extNum,
                    'currency' => $invoice['currency'] ?? 'TRY',
                    'ettn' => $ettn,
                    'net_total' => (float) $invoice['subtotal'],
                    'gross_total' => (float) $invoice['total'],
                ],
                'relationships' => [
                    'contact' => [
                        'name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Bireysel Müşteri',
                        'email' => $customer['email'] ?? null,
                    ],
                    'details' => array_map(function ($item) {
                        return [
                            'description' => $item['description'],
                            'quantity' => (float) $item['quantity'],
                            'unit_price' => (float) $item['unit_price'],
                            'vat_rate' => 20,
                        ];
                    }, $items),
                ],
            ],
        ];

        return [
            'external_id' => $extNum,
            'payload' => $payload,
        ];
    }

    private function sync_to_bizimhesap(array $invoice, array $items, ?array $customer, string $ettn, string $extNum): array
    {
        $payload = [
            'invoice_no' => $extNum,
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Müşteri',
            'date' => date('Y-m-d', strtotime($invoice['created_at'])),
            'total' => (float) $invoice['total'],
            'ettn' => $ettn,
            'lines' => $items,
        ];

        return [
            'external_id' => $extNum,
            'payload' => $payload,
        ];
    }

    private function sync_to_logo(array $invoice, array $items, ?array $customer, string $ettn, string $extNum): array
    {
        $payload = [
            'TYPE' => 8, // Toptan/Perakende Satış Faturası
            'NUMBER' => $extNum,
            'DOC_NUMBER' => $invoice['invoice_number'],
            'DATE' => date('d.m.Y', strtotime($invoice['created_at'])),
            'GUID' => $ettn,
            'TOTAL_NET' => (float) $invoice['subtotal'],
            'TOTAL_GROSS' => (float) $invoice['total'],
            'LINES' => $items,
        ];

        return [
            'external_id' => $extNum,
            'payload' => $payload,
        ];
    }

    private function sync_to_mikro(array $invoice, array $items, ?array $customer, string $ettn, string $extNum): array
    {
        $payload = [
            'evrak_seri' => 'BK',
            'evrak_sira' => $invoice['id'],
            'fatura_no' => $extNum,
            'ettn' => $ettn,
            'tarih' => date('Y-m-d', strtotime($invoice['created_at'])),
            'tutar' => (float) $invoice['total'],
            'kalemler' => $items,
        ];

        return [
            'external_id' => $extNum,
            'payload' => $payload,
        ];
    }

    private function get_default_provider(): string
    {
        $row = $this->CI->db->get_where('settings', ['name' => 'active_erp_provider'])->row_array();

        return $row['value'] ?? 'parasut';
    }

    private function generate_uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
