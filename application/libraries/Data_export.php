<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Data Export (Faz 30, KVKK/GDPR).
 *
 * Builds a full personal-data export for a customer and packages it to disk.
 * Every data section is gathered in isolation (per-section try/catch) so a
 * bug or unrelated table issue in one section never blocks the customer from
 * getting the rest of their data - the failure is recorded in meta.excluded
 * instead, for transparency.
 * ---------------------------------------------------------------------------- */

class Data_export
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * How long a generated export stays downloadable.
     */
    private const EXPIRES_HOURS = 72;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Gather every personal-data section for a customer into one array. Never throws - a section
     * that fails is recorded by key in $data['meta']['excluded'] and left out, rather than aborting
     * the whole export.
     *
     * @param int $customer_id
     * @return array
     */
    public function build(int $customer_id): array
    {
        $data = [
            'meta' => [
                'generated_at' => date('c'),
                'customer_id' => $customer_id,
                'excluded' => [],
            ],
        ];

        $this->CI->load->model('customers_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('orders_model');
        $this->CI->load->model('payment_transactions_model');
        $this->CI->load->model('consents_model');
        $this->CI->load->model('whatsapp_messages_model');
        $this->CI->load->model('customer_memberships_model');

        $customer = null;

        try {
            $customer = $this->CI->customers_model->find($customer_id);
            $data['customer'] = $customer;
        } catch (Throwable $e) {
            log_message('error', 'Data_export::build() - "customer" section failed for customer ' . $customer_id . ': ' . $e->getMessage());
            $data['customer'] = null;
            $data['meta']['excluded'][] = 'customer';
        }

        $sections = [
            'appointments' => fn() => $this->CI->appointments_model->get_for_customer($customer_id),
            'orders' => fn() => $this->CI->orders_model->get_for_customer($customer_id),
            'payment_transactions' => fn() => $this->CI->payment_transactions_model->get_for_customer($customer_id),
            'consents' => fn() => $this->CI->consents_model->get_for_customer($customer_id),
            'whatsapp_messages' => fn() => $this->CI->whatsapp_messages_model->get_for_customer($customer_id, $customer['phone_number'] ?? null),
            'memberships' => fn() => $this->CI->customer_memberships_model->get_for_customer($customer_id),
            'membership_sessions' => fn() => $this->CI->customer_memberships_model->get_sessions_for_customer($customer_id),
        ];

        foreach ($sections as $key => $builder) {
            try {
                $data[$key] = $builder();
            } catch (Throwable $e) {
                log_message('error', 'Data_export::build() - "' . $key . '" section failed for customer ' . $customer_id . ': ' . $e->getMessage());
                $data[$key] = [];
                $data['meta']['excluded'][] = $key;
            }
        }

        return $data;
    }

    /**
     * Render the export as pretty-printed JSON (Turkish characters left readable, not \uXXXX-escaped).
     *
     * @param array $data
     * @return string
     */
    public function to_json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render the export as a simple, human-readable HTML page (a business owner or customer with no
     * technical background can open this directly in a browser - the JSON file is for anyone who
     * wants the raw machine-readable copy).
     *
     * @param array $data
     * @return string
     */
    public function to_html(array $data): string
    {
        $sections = $data;
        unset($sections['meta']);

        ob_start();
        ?>
        <!doctype html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <title>Kişisel Veri Dışa Aktarımı</title>
            <style>
                body { font-family: sans-serif; max-width: 900px; margin: 30px auto; padding: 0 15px; color: #222; }
                h1 { font-size: 22px; }
                h2 { font-size: 16px; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
                table { border-collapse: collapse; width: 100%; margin-top: 8px; }
                th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 13px; text-align: left; vertical-align: top; word-break: break-word; }
                th { background: #f6f6f6; }
                .meta { color: #666; font-size: 12px; }
                .empty { color: #999; font-style: italic; }
            </style>
        </head>
        <body>
        <h1>Kişisel Veri Dışa Aktarımı</h1>
        <p class="meta">
            Oluşturulma tarihi: <?= e($data['meta']['generated_at'] ?? '') ?><br>
            <?php if (!empty($data['meta']['excluded'])): ?>
                Not: <?= e(implode(', ', $data['meta']['excluded'])) ?> bölümleri işlenirken bir hata oluştu ve dahil edilemedi.
            <?php endif; ?>
        </p>
        <?php foreach ($sections as $key => $value): ?>
            <h2><?= e((string) $key) ?></h2>
            <?php if (empty($value)): ?>
                <p class="empty">Kayıt yok.</p>
            <?php elseif (is_array($value) && array_is_list($value)): ?>
                <?php if (empty($value)): ?>
                    <p class="empty">Kayıt yok.</p>
                <?php else: ?>
                    <?php $columns = array_keys((array) $value[0]); ?>
                    <table>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?= e((string) $column) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php foreach ($value as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= e((string) ($row[$column] ?? '')) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <table>
                    <?php foreach ((array) $value as $field => $field_value): ?>
                        <tr>
                            <th><?= e((string) $field) ?></th>
                            <td><?= e(is_scalar($field_value) ? (string) $field_value : json_encode($field_value, JSON_UNESCAPED_UNICODE)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Write the export to disk under storage/exports/{year}/{random}/ and return the info needed to
     * mark the data_requests row ready. Bundles into a single .zip when the zip extension is
     * available (format='zip'); otherwise falls back to loose files with export.json as the primary
     * downloadable file (format='files') - either way, the returned file_path always points at ONE
     * real file (never a directory), matching what Customers_model::invalidate_data_exports() expects.
     *
     * @param array $data
     * @return array ['file_path' => string, 'file_size' => int, 'format' => string]
     */
    public function write_output(array $data): array
    {
        $rel_dir = 'exports/' . date('Y') . '/' . bin2hex(random_bytes(16));
        $abs_dir = storage_path($rel_dir);

        // 0755, matching every sibling folder under storage/ (backups/logs/uploads) - the
        // unguessable random directory name plus the token_hash check in
        // Customer_portal::download_export() are the actual security boundary here, not filesystem
        // permissions. A tighter mode would only break the case where the file is written by one
        // process (a CLI queue worker) and served by another (Apache/www-data) - exactly what broke
        // during testing with 0750.
        if (!mkdir($abs_dir, 0755, true) && !is_dir($abs_dir)) {
            throw new RuntimeException('Dışa aktarma dizini oluşturulamadı: ' . $abs_dir);
        }

        file_put_contents($abs_dir . '/export.json', $this->to_json($data));
        file_put_contents($abs_dir . '/export.html', $this->to_html($data));
        file_put_contents($abs_dir . '/BENIOKU.txt', $this->build_readme());

        if (extension_loaded('zip')) {
            $zip_path = $abs_dir . '/export.zip';
            $zip = new ZipArchive();

            if ($zip->open($zip_path, ZipArchive::CREATE) === true) {
                $zip->addFile($abs_dir . '/export.json', 'export.json');
                $zip->addFile($abs_dir . '/export.html', 'export.html');
                $zip->addFile($abs_dir . '/BENIOKU.txt', 'BENIOKU.txt');
                $zip->close();

                @unlink($abs_dir . '/export.json');
                @unlink($abs_dir . '/export.html');
                @unlink($abs_dir . '/BENIOKU.txt');

                return [
                    'file_path' => $rel_dir . '/export.zip',
                    'file_size' => filesize($zip_path),
                    'format' => 'zip',
                ];
            }

            log_message('error', 'Data_export::write_output() - ZipArchive::open() failed for ' . $zip_path . ', falling back to loose files.');
        }

        return [
            'file_path' => $rel_dir . '/export.json',
            'file_size' => filesize($abs_dir . '/export.json'),
            'format' => 'files',
        ];
    }

    /**
     * Build, write to disk, and mark a data_requests row ready. Returns the RAW download token -
     * the caller (handle_queued_export()) must deliver it to the customer immediately (e.g. via
     * email) since only its hash is ever persisted (see migration 127 / Data_requests_model).
     *
     * @param int $customer_id
     * @param int $request_id
     * @return string The raw download token.
     */
    public function build_and_store(int $customer_id, int $request_id): string
    {
        $this->CI->load->model('data_requests_model');

        $this->CI->data_requests_model->mark_processing($request_id);

        $data = $this->build($customer_id);
        $output = $this->write_output($data);

        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRES_HOURS . ' hours'));

        $this->CI->data_requests_model->mark_ready(
            $request_id,
            $token_hash,
            $expires,
            $output['file_path'],
            $output['file_size'],
            $output['format'],
        );

        return $token;
    }

    /**
     * Queued job handler for 'data_requests.export' (see Job_dispatcher::HANDLERS). Builds and
     * stores the export, then emails the customer a one-time download link. Mirrors
     * Webhooks_client::handle_queued_delivery()'s try/catch-and-log-only shape - a queue worker
     * must never let one bad job crash the batch.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload ['request_id' => int]
     * @return void
     */
    public function handle_queued_export($CI, array $payload): void
    {
        try {
            $request_id = $payload['request_id'] ?? null;

            if (!$request_id) {
                log_message('warning', 'Data_export::handle_queued_export() - request_id is missing');
                return;
            }

            $CI->load->model('data_requests_model');

            $request = $CI->data_requests_model->find((int) $request_id);

            if (!$request) {
                log_message('warning', 'Data_export::handle_queued_export() - request not found: ' . $request_id);
                return;
            }

            if ($request['status'] !== 'pending') {
                // Already processed/expired/failed - never re-run (e.g. duplicate job delivery).
                return;
            }

            $customer_id = (int) $request['id_users'];

            $CI->load->model('customers_model');
            $customer = $CI->customers_model->find($customer_id);

            $token = $CI->data_export->build_and_store($customer_id, (int) $request_id);
        } catch (Throwable $e) {
            // Failure BEFORE the export was produced/marked ready - the request is genuinely failed.
            log_message('error', 'Data_export::handle_queued_export() failed to build export: ' . $e->getMessage());

            if (!empty($payload['request_id'])) {
                try {
                    $CI->load->model('data_requests_model');
                    $CI->data_requests_model->mark_failed((int) $payload['request_id'], $e->getMessage());
                } catch (Throwable $inner) {
                    log_message('error', 'Data_export::handle_queued_export() - also failed to mark_failed(): ' . $inner->getMessage());
                }
            }

            return;
        }

        // The export itself is already built and marked 'ready' at this point - a failure below is
        // just a delivery/notification problem, never grounds to flip a successful export to 'failed'.
        try {
            if (empty($customer['email'])) {
                log_message('warning', 'Data_export::handle_queued_export() - customer ' . $customer_id . ' has no email, export ready but not notified.');
                return;
            }

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
            ];

            $CI->load->library('email_messages');

            $download_link = site_url('customer_portal/download_export/' . $request_id . '/' . $token);

            $CI->email_messages->send_data_export_ready($download_link, self::EXPIRES_HOURS . ' saat', $customer['email'], $settings);
        } catch (Throwable $e) {
            log_message('error', 'Data_export::handle_queued_export() - export ready but notification email failed for request ' . $request_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Plain-text README bundled alongside every export, explaining the files to a non-technical
     * recipient.
     *
     * @return string
     */
    private function build_readme(): string
    {
        return <<<TEXT
        KİŞİSEL VERİ DIŞA AKTARIMINIZ

        Bu pakette kişisel verilerinizin bir kopyası bulunmaktadır:

        - export.json: Verilerinizin ham, makine tarafından okunabilir kopyası.
        - export.html: Aynı verilerin tarayıcınızda kolayca görüntüleyebileceğiniz okunabilir hali.

        Herhangi bir sorunuz olursa işletmeyle iletişime geçebilirsiniz.
        TEXT;
    }
}
