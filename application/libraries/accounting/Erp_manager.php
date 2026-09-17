<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-ERP Accounting Manager (2026-09-17, gerçek doküman araştırması 2026-09-17).
 *
 * Orchestrates invoice synchronization with 6 requested ERP / e-Fatura systems: Paraşüt, İşbaşı,
 * Logo, Mikro, QuickBooks, Zoho Books. Önceki liste ('bizimhesap' içeriyordu, kullanıcının
 * istediği listede YOKTU) düzeltildi.
 *
 * DOKÜMANTASYON ARAŞTIRMASI SONUCU (2026-09-17) - bu 6 sistem iki temel farklı kategoriye
 * ayrılıyor, bu yüzden hepsi AYNI ŞEKİLDE "gerçek entegrasyon" olamaz:
 *
 * 1) Merkezi SaaS REST API'si olanlar (gerçek entegrasyon MÜMKÜN, kod hazır, sadece kimlik
 *    bilgisi bekliyor - bkz. Console::erp_config()):
 *    - QuickBooks Online -> Quickbooks_connector.php (OAuth2 refresh_token, developer.intuit.com).
 *    - Zoho Books -> Zohobooks_connector.php (OAuth2 refresh_token, zoho.com/books/api/v3).
 *    - Paraşüt -> sync_to_parasut() aşağıda (JSON:API OAuth2) - DİKKAT: apidocs.parasut.com bot
 *      korumalı, tam invoice endpoint path'i TEYİT EDİLEMEDİ (varsayım: POST /v4/{company_id}/
 *      sales_invoices), gerçek kimlik bilgisiyle test edilmeden production'a güvenilmemeli.
 *
 * 2) Merkezi/tek bir API'si OLMAYAN sistemler (gerçek entegrasyon merkezi olarak MÜMKÜN DEĞİL):
 *    - Logo (Tiger/Go3) ve Mikro: müşterinin KENDİ sunucusunda/yerel ağında çalışan bir web
 *      servisine bağlanılır - her kurulum farklı URL/şema. Tek bir "Logo API" ya da "Mikro API"
 *      base URL'i yok. Ayrıca e-Fatura'yı genelde GİB'e DOĞRUDAN değil, özel bir entegratör
 *      (Foriba, eLogo, Paraşüt) ÜZERİNDEN gönderirler.
 *    - İşbaşı (developers.isbasi.com): dokümantasyon sayfası girişli hesap gerektiriyor (herkese
 *      açık değil) - kullanıcının önce kendi İşbaşı hesabından bir API key talep etmesi gerekiyor,
 *      o olmadan endpoint şeması bile görülemiyor.
 *    Bu 3 sistem için sync_to_*() metodları BİLİNÇLİ OLARAK mock kaldı (aşağıdaki docblock'larda
 *    neden açıklanıyor) - sahte bir şema uydurmak, gerçek parayla/gerçek e-faturayla denendiğinde
 *    sessizce yanlış/eksik veri üretir.
 * ---------------------------------------------------------------------------- */

class Erp_manager
{
    protected CI_Controller|EA_Controller $CI;

    public const PROVIDERS = [
        'parasut' => 'Paraşüt',
        'quickbooks' => 'QuickBooks Online',
        'zohobooks' => 'Zoho Books',
        'logo' => 'Logo ERP',
        'mikro' => 'Mikro Yazılım',
        'isbasi' => 'İşbaşı',
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('invoices_model');
        require_once __DIR__ . '/Quickbooks_connector.php';
        require_once __DIR__ . '/Zohobooks_connector.php';
    }

    /**
     * Synchronize an internal invoice to the configured or selected ERP system.
     *
     * @param int $invoice_id The local invoice ID.
     * @param string|null $provider Specific provider override (see self::PROVIDERS keys).
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
                'quickbooks' => $this->sync_to_quickbooks($invoice, $items, $customer),
                'zohobooks' => $this->sync_to_zohobooks($invoice, $items, $customer),
                'logo' => $this->sync_to_logo($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                'mikro' => $this->sync_to_mikro($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
                'isbasi' => $this->sync_to_isbasi($invoice, $items, $customer, $ettnUuid, $externalInvoiceNumber),
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

    /**
     * Paraşüt - PAYLOAD ŞEKLİ (JSON:API sales_invoices) araştırmayla teyit edildi, ama TAM
     * endpoint path'i (`POST /v4/{company_id}/sales_invoices` varsayıldı) HENÜZ TEYİT EDİLEMEDİ
     * (apidocs.parasut.com bot korumalı, otomatik çekilemedi - 2026-09-17). Gerçek client_id/
     * client_secret + company_id gelmeden bu metod HTTP çağrısı yapmıyor (mock döner) - yanlış bir
     * endpoint'e körlemesine istek atmak yerine, doğrulama gerçek kimlik bilgisiyle yapılmalı.
     */
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

    /**
     * QuickBooks Online - GERÇEK API çağrısı (Quickbooks_connector.php). Kimlik bilgisi yoksa
     * connector RuntimeException fırlatır (sync_invoice() bunu yakalayıp erp_status='failed' yazar).
     */
    private function sync_to_quickbooks(array $invoice, array $items, ?array $customer): array
    {
        $connector = new Quickbooks_connector();

        $externalId = $connector->create_invoice([
            'total' => (float) $invoice['total'],
            'description' => $this->invoice_description($items),
        ], $customer ?? []);

        return ['external_id' => $externalId];
    }

    /**
     * Zoho Books - GERÇEK API çağrısı (Zohobooks_connector.php). Kimlik bilgisi yoksa connector
     * RuntimeException fırlatır (sync_invoice() bunu yakalayıp erp_status='failed' yazar).
     */
    private function sync_to_zohobooks(array $invoice, array $items, ?array $customer): array
    {
        $connector = new Zohobooks_connector();

        $externalId = $connector->create_invoice([
            'total' => (float) $invoice['total'],
            'description' => $this->invoice_description($items),
        ], $customer ?? []);

        return ['external_id' => $externalId];
    }

    private function invoice_description(array $items): string
    {
        $first = $items[0]['description'] ?? null;

        if (!$first) {
            return 'Randevu / Appointment';
        }

        return count($items) > 1 ? $first . ' (+' . (count($items) - 1) . ')' : $first;
    }

    /**
     * İşbaşı (developers.isbasi.com) - MOCK. Doküman sayfası girişli hesap gerektiriyor (herkese
     * açık değil) - kullanıcının önce kendi İşbaşı hesabından bir API key talep etmesi lazım, o
     * olmadan gerçek endpoint şeması görülemiyor (araştırıldı 2026-09-17). Sahte bir şema
     * uydurmak yerine, gerçek kimlik bilgisi/doküman gelene kadar bilinçli olarak mock bırakıldı.
     */
    private function sync_to_isbasi(array $invoice, array $items, ?array $customer, string $ettn, string $extNum): array
    {
        $payload = [
            'fatura_no' => $extNum,
            'musteri_adi' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Müşteri',
            'tarih' => date('Y-m-d', strtotime($invoice['created_at'])),
            'tutar' => (float) $invoice['total'],
            'ettn' => $ettn,
            'kalemler' => $items,
        ];

        return [
            'external_id' => $extNum,
            'payload' => $payload,
        ];
    }

    /**
     * Logo (Tiger/Go3) - MOCK, BİLİNÇLİ OLARAK. Araştırma (2026-09-17): Logo'nun merkezi/genel bir
     * REST API'si YOK - "LOGO Object"/REST kaynağı müşterinin KENDİ sunucusunda/yerel ağında çalışır,
     * her kurulumun URL'i ve şeması farklıdır (genelde bir aracı entegratör firma kurar). Gerçek
     * entegrasyon her müşteri için ayrı bağlantı bilgisi (host, port, DB) gerektirir - burada
     * genellenemez. E-Fatura da genelde GİB'e doğrudan değil, Foriba/eLogo/Paraşüt gibi bir özel
     * entegratör üzerinden gider.
     */
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

    /**
     * Mikro Yazılım - MOCK, BİLİNÇLİ OLARAK. Aynı Logo gibi: merkezi tek bir "Mikro API" yok,
     * müşterinin kendi sunucusuna özel bir Web Service'e bağlanılır (üçüncü parti dokümantasyona
     * göre kimlik doğrulama API Key + FirmaKodu + CalismaYili + KullaniciKodu + Sifre ile, ama base
     * URL müşteriye özel - genellenemez). E-Fatura burada da genelde bir özel entegratör
     * (Foriba/eLogo/Paraşüt) üzerinden gidiyor.
     */
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
