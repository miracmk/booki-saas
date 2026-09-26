<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Zero-Dependency High-Performance Spreadsheet & CSV Importer
 *
 * Supports CSV (UTF-8, ISO-8859-9, comma, semicolon, tab) and native XLSX
 * (using PHP ZipArchive + XMLReader/SimpleXML) without external bloat.
 * -------------------------------------------------------------------------- */

class Spreadsheet_importer
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Parse any CSV or XLSX file and extract clean rows.
     */
    public function parse_file(string $file_path, string $original_name = ''): array
    {
        if (!file_exists($file_path)) {
            throw new InvalidArgumentException("Dosya bulunamadı: {$file_path}");
        }

        $ext = strtolower(pathinfo($original_name ?: $file_path, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return $this->parse_xlsx($file_path);
        }

        return $this->parse_csv($file_path);
    }

    /**
     * Native CSV Parser with delimiter detection and UTF-8 conversion.
     */
    public function parse_csv(string $file_path): array
    {
        $content = file_get_contents($file_path);
        if ($content === false || trim($content) === '') {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        // Strip UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Convert encoding if Windows-1254 / ISO-8859-9
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-9, Windows-1254, ISO-8859-1');
        }

        // Detect delimiter (semicolon, comma, tab)
        $first_line = strtok($content, "\r\n");
        $semicolons = substr_count($first_line, ';');
        $commas = substr_count($first_line, ',');
        $tabs = substr_count($first_line, "\t");

        $delimiter = ';';
        if ($commas > $semicolons && $commas >= $tabs) {
            $delimiter = ',';
        } elseif ($tabs > $semicolons && $tabs > $commas) {
            $delimiter = "\t";
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $headers = [];
        $rows = [];

        foreach ($lines as $line_idx => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $cols = str_getcsv($line, $delimiter, '"');
            $cols = array_map(function ($val) {
                return trim((string) $val);
            }, $cols);

            if ($line_idx === 0 || empty($headers)) {
                $headers = $cols;
                continue;
            }

            // Pad or trim to match headers
            if (count($cols) < count($headers)) {
                $cols = array_pad($cols, count($headers), '');
            } else {
                $cols = array_slice($cols, 0, count($headers));
            }

            $assoc_row = [];
            foreach ($headers as $idx => $header_name) {
                $clean_header = trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', $header_name));
                $assoc_row[$clean_header] = $cols[$idx] ?? '';
            }

            $rows[] = $assoc_row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Native XLSX Parser using ZipArchive and XML parser.
     */
    public function parse_xlsx(string $file_path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP ZipArchive eklentisi bulunamadı. Lütfen CSV formatı kullanın.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file_path) !== true) {
            throw new RuntimeException('XLSX dosyası açılamadı.');
        }

        // 1. Read shared strings
        $shared_strings = [];
        $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared_strings_xml !== false) {
            $xml = simplexml_load_string($shared_strings_xml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $shared_strings[] = (string) ($si->t ?? $si->r->t ?? '');
                }
            }
        }

        // 2. Read first worksheet
        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet_xml === false) {
            throw new RuntimeException('XLSX çalışma sayfası (sheet1.xml) bulunamadı.');
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $raw_rows = [];
        foreach ($xml->sheetData->row as $r) {
            $row_cells = [];
            foreach ($r->c as $c) {
                $cell_val = (string) $c->v;
                $cell_type = (string) $c['t'];

                if ($cell_type === 's' && isset($shared_strings[(int) $cell_val])) {
                    $cell_val = $shared_strings[(int) $cell_val];
                }

                $row_cells[] = trim($cell_val);
            }
            if (!empty(array_filter($row_cells))) {
                $raw_rows[] = $row_cells;
            }
        }

        if (empty($raw_rows)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = array_shift($raw_rows);
        $rows = [];

        foreach ($raw_rows as $cols) {
            if (count($cols) < count($headers)) {
                $cols = array_pad($cols, count($headers), '');
            } else {
                $cols = array_slice($cols, 0, count($headers));
            }

            $assoc = [];
            foreach ($headers as $idx => $h) {
                $assoc[$h] = $cols[$idx] ?? '';
            }
            $rows[] = $assoc;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Map rows into leads table, performing duplicate detection and batch persistence.
     */
    public function import_leads(array $rows, array $column_mapping, string $duplicate_action = 'skip', string $actor = 'Admin'): array
    {
        $this->CI->load->model('leads_model');

        $imported = 0;
        $updated = 0;
        $duplicates = 0;
        $failed = 0;
        $errors = [];

        $stage_map = [
            'planli' => 'Visit Planned',
            'ziyaret edildi' => 'Visited',
            'demo sunuldu' => 'Demo Presented',
            'demo satisi yapildi' => 'Trial Started',
            'yeniden ziyaret' => 'Follow-up',
            'kazanildi' => 'Won',
            'kaybedildi' => 'Lost',
        ];

        foreach ($rows as $index => $row) {
            $row_num = $index + 2; // account for header line

            // Resolve mapped values
            $lead_data = [];
            foreach ($column_mapping as $target_field => $source_col) {
                if ($source_col !== '' && isset($row[$source_col])) {
                    $lead_data[$target_field] = trim((string) $row[$source_col]);
                }
            }

            $name = $lead_data['name'] ?? '';
            if ($name === '') {
                $failed++;
                $errors[] = [
                    'row' => $row_num,
                    'data' => $row,
                    'reason' => 'İşletme adı (Business Name) alanı zorunludur.',
                ];
                continue;
            }

            $phone = $lead_data['phone'] ?? '';
            $email = $lead_data['email'] ?? '';
            $address = $lead_data['address'] ?? '';

            // Check duplicate
            $duplicate = $this->CI->leads_model->check_duplicate($name, $phone, $email, $address);

            if ($duplicate) {
                $duplicates++;

                if ($duplicate_action === 'skip') {
                    continue;
                }

                if ($duplicate_action === 'update') {
                    $this->CI->leads_model->update_lead((int) $duplicate['id'], $lead_data, $actor);
                    $this->CI->leads_model->add_activity(
                        (int) $duplicate['id'],
                        'note',
                        'İçe Aktarma İle Güncellendi',
                        'Excel/CSV içe aktarma sırasında mevcut kayıt güncellendi.',
                        $actor
                    );
                    $updated++;
                    continue;
                }
                // 'create_anyway' continues below
            }

            // Normalize Stage
            $raw_stage_lower = mb_strtolower($lead_data['stage'] ?? 'planli');
            $lead_data['stage'] = $stage_map[$raw_stage_lower] ?? ($lead_data['stage'] ?? 'Visit Planned');
            $lead_data['sector'] = $lead_data['sector'] ?? '💅 Güzellik & Tırnak';
            $lead_data['district'] = $lead_data['district'] ?? 'Nilüfer';
            $lead_data['lead_source'] = $lead_data['lead_source'] ?? 'Excel / CSV Import';
            $lead_data['owner_name'] = $lead_data['owner_name'] ?? $actor;

            try {
                $new_id = $this->CI->leads_model->create_lead($lead_data, $actor);
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $row_num,
                    'data' => $row,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        // Save import job
        $this->CI->db->insert('import_jobs', [
            'file_name' => 'upload_' . date('Ymd_His') . '.csv',
            'import_type' => 'leads',
            'total_rows' => count($rows),
            'imported_count' => $imported,
            'updated_count' => $updated,
            'duplicate_count' => $duplicates,
            'failed_count' => $failed,
            'status' => 'completed',
            'created_by' => $actor,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $job_id = $this->CI->db->insert_id();

        foreach ($errors as $err) {
            $this->CI->db->insert('import_errors', [
                'id_jobs' => $job_id,
                'row_number' => $err['row'],
                'raw_data_json' => json_encode($err['data'], JSON_UNESCAPED_UNICODE),
                'error_reason' => $err['reason'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'job_id' => $job_id,
            'total_rows' => count($rows),
            'imported' => $imported,
            'updated' => $updated,
            'duplicates' => $duplicates,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Generate standard downloadable template CSV.
     */
    public function get_template_csv(string $type = 'leads'): string
    {
        if ($type === 'customers') {
            $headers = ['Ad', 'Soyad', 'Telefon', 'E-Posta', 'Doğum Tarihi (YYYY-AA-GG)', 'Notlar', 'Etiketler'];
            $sample = ['Ayşe', 'Yılmaz', '+90 532 111 22 33', 'ayse@example.com', '1990-05-15', 'Düzenli müşteri', 'VIP, Cilt Bakımı'];
        } elseif ($type === 'services') {
            $headers = ['Hizmet Adı', 'Kategori', 'Süre (Dakika)', 'Fiyat (TL)', 'Açıklama'];
            $sample = ['Manikür & Kalıcı Oje', 'Tırnak', '45', '450.00', 'Klasik manikür ve kalıcı oje uygulaması'];
        } elseif ($type === 'resources') {
            $headers = ['Kaynak Adı', 'Kaynak Türü', 'Kapasite', 'Konum / Not'];
            $sample = ['Oda 1', 'Room', '1', 'Giriş kat cilt bakım odası'];
        } else {
            // Default: Leads
            $headers = [
                'İşletme Adı', 'Yetkili Kişi', 'Telefon', 'WhatsApp', 'E-Posta', 
                'Sektör', 'İlçe', 'Adres', 'Web Sitesi', 'Instagram', 
                'Satış Aşaması', 'Hedef Paket', 'Ödeme Periyodu', 'Notlar', 'Öncelik'
            ];
            $sample = [
                'Örnek Güzellik Salonu', 'Zeynep Hanım', '+90 532 100 20 30', '+90 532 100 20 30', 'info@ornek.com',
                '💅 Güzellik & Tırnak', 'Nilüfer', 'FSM Bulvarı No:12', 'www.ornek.com', '@ornekguzellik',
                'Ziyaret Planlandı', 'Professional', 'Aylık', 'WhatsApp otomatik hatırlatma istiyor', 'high'
            ];
        }

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $headers)) . "\r\n";
        $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $sample)) . "\r\n";

        return $output;
    }

    /**
     * Generate error rows CSV download.
     */
    public function get_error_csv(array $errors): string
    {
        $output = "\xEF\xBB\xBF";
        $headers = ['Satır No', 'Hata Gerekçesi', 'Ham Veri'];
        $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $headers)) . "\r\n";

        foreach ($errors as $err) {
            $raw_summary = json_encode($err['data'] ?? [], JSON_UNESCAPED_UNICODE);
            $row = [$err['row'] ?? '', $err['reason'] ?? '', $raw_summary];
            $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n";
        }

        return $output;
    }
}

