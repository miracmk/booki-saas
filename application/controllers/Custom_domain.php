<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - tenant self-service custom domain (2026-09-10).
 *
 * Lets an admin point their own domain at their BooKi account without
 * filing a support request, mirroring the "add domain -> DNS instructions ->
 * verify -> status" flow of e.g. Zoho Billing's organization custom-domain
 * mapping. Three-stage pipeline, split by trust boundary:
 *
 *   1. THIS CONTROLLER (runs inside the tenant-facing web app, cannot touch
 *      docker/certbot/nginx by design) - collects the domain, proves the
 *      tenant actually controls its DNS via a TXT-record challenge + a
 *      CNAME/A check (read-only PHP dns_get_record()/checkdnsrr(), no shell-
 *      out), and writes the result to the MASTER `tenants` table via a
 *      throwaway 'default'-group connection (see master_setting()'s docblock
 *      in tenant_helper.php for why: $this->db is the TENANT db by the time
 *      any controller method runs, resolve_tenant() already swapped it).
 *   2. Console.php::domain_requests_pending() / domain_provision_mark() -
 *      the CLI-only handoff, unreachable from the web.
 *   3. scripts/domain-worker.sh (HOST-side, cron) - the only thing that ever
 *      actually runs certbot/nginx, exactly as before (scripts/add-custom-
 *      domain.sh is unchanged and still does the real provisioning).
 *
 * `custom_domain_pending` is deliberately separate from the LIVE, routed
 * `custom_domain` column (App_Controller::resolve_tenant() only ever reads the
 * latter) - nothing in this controller can affect which domain currently
 * routes traffic until the host worker promotes it.
 * ---------------------------------------------------------------------------- */

class Custom_domain extends App_Controller
{
    // BooKi (2026-09-10) - where a verified/active tenant domain should ultimately point.
    // Mirrors scripts/add-custom-domain.sh's own DNS check (same server IP / canonical CNAME target).
    private static function canonical_cname_target(): string
    {
        return getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
    }
    private const CANONICAL_SERVER_IP = '168.231.109.167';

    private const HOSTNAME_PATTERN = '/^(?=.{4,255}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/';

    /**
     * @return array|null The current tenant's own row from the MASTER `tenants` table, or null in
     *   single-tenant/standalone mode (where this feature does not apply at all).
     */
    private function master_tenant_row(): ?array
    {
        // BooKi (2026-09-11 fix) - NOT is_multi_tenant_mode() here: by the time a controller
        // method runs, resolve_tenant() has already swapped $this->db to the TENANT's own database
        // (which has no `tenants` table), so that check always reads as false and this feature looked
        // "multi-tenant only, but broken on every real tenant". tenant_context() is what actually means
        // "this request was resolved to some tenant" - see App_Controller::enforce_onboarding()'s
        // identical note for the same bug class.
        if (!tenant_context()) {
            return null;
        }

        $tenant = tenant_context();

        if (!$tenant) {
            return null;
        }

        $master_db = $this->load->database('default', true);

        return $master_db->get_where('tenants', ['id' => $tenant['id']])->row_array() ?: null;
    }

    /**
     * @param array $fields Columns to update on the current tenant's master row.
     */
    private function update_master_tenant(array $fields): void
    {
        $tenant = tenant_context();

        $master_db = $this->load->database('default', true);

        $fields['updated_at'] = date('Y-m-d H:i:s');

        $master_db->update('tenants', $fields, ['id' => $tenant['id']]);
    }

    /**
     * Render the "Özel Alan Adı" settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('custom_domain')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $this->load->model('roles_model');
        $this->load->library('accounts');

        $row = $this->master_tenant_row();

        html_vars([
            'page_title' => 'Özel Alan Adı',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
            'multi_tenant' => (bool) tenant_context(),
            'domain_state' => $row,
            'canonical_target' => self::canonical_cname_target(),
            'canonical_ip' => self::CANONICAL_SERVER_IP,
        ]);

        $this->load->view('pages/custom_domain');
    }

    /**
     * Request a new custom domain: validates the hostname, checks it isn't already claimed by
     * another tenant, issues a fresh TXT-record verification token, and returns the exact DNS records
     * the tenant needs to create.
     */
    public function request_domain(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            if (!tenant_context()) {
                throw new RuntimeException('Bu özellik yalnızca çoklu kiracılı bulut dağıtımında kullanılabilir.');
            }

            check('domain', 'string');

            $domain = strtolower(trim((string) request('domain')));
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');

            if (!preg_match(self::HOSTNAME_PATTERN, $domain)) {
                throw new InvalidArgumentException('Geçerli bir alan adı girin (örn. rezervasyon.firmaniz.com).');
            }

            if (str_ends_with($domain, '.kibusiness.co')) {
                throw new InvalidArgumentException('kibusiness.co alt alan adları özel alan adı olarak eklenemez.');
            }

            $master_db = $this->load->database('default', true);

            $tenant = tenant_context();

            $taken = $master_db
                ->where('id !=', $tenant['id'])
                ->group_start()
                ->where('custom_domain', $domain)
                ->or_where('custom_domain_pending', $domain)
                ->group_end()
                ->get('tenants')
                ->row_array();

            if ($taken) {
                throw new InvalidArgumentException('Bu alan adı zaten başka bir hesap tarafından kullanılıyor.');
            }

            $token = bin2hex(random_bytes(16));

            $this->update_master_tenant([
                'custom_domain_pending' => $domain,
                'custom_domain_status' => 'pending_dns',
                'custom_domain_verification_token' => $token,
                'custom_domain_requested_at' => date('Y-m-d H:i:s'),
                'custom_domain_verified_at' => null,
                'custom_domain_last_error' => null,
            ]);

            audit_log('custom_domain.requested', 'tenant', (int) $tenant['id'], ['domain' => $domain]);

            json_response([
                'success' => true,
                'domain' => $domain,
                'txt_host' => '_ki-verify.' . $domain,
                'txt_value' => 'ki-verify=' . $token,
                'cname_target' => self::canonical_cname_target(),
                'a_target' => self::CANONICAL_SERVER_IP,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Re-check DNS for the tenant's currently pending domain. Read-only (dns_get_record()/
     * checkdnsrr(), no shell-out) - safe to call as often as the UI wants ("Doğrula" button, or the
     * page's own poll timer).
     */
    public function verify_domain(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $row = $this->master_tenant_row();

            if (!$row || empty($row['custom_domain_pending'])) {
                throw new InvalidArgumentException('Önce bir alan adı talep etmelisiniz.');
            }

            if ($row['custom_domain_status'] === 'active') {
                json_response(['success' => true, 'status' => 'active']);
                return;
            }

            $domain = $row['custom_domain_pending'];
            $expected_txt = 'ki-verify=' . $row['custom_domain_verification_token'];

            $txt_ok = $this->dns_txt_matches('_ki-verify.' . $domain, $expected_txt);
            $target_ok = $this->dns_points_here($domain);

            if ($txt_ok && $target_ok) {
                $this->update_master_tenant([
                    'custom_domain_status' => 'dns_verified',
                    'custom_domain_verified_at' => date('Y-m-d H:i:s'),
                    'custom_domain_last_error' => null,
                ]);

                audit_log('custom_domain.dns_verified', 'tenant', (int) $row['id'], ['domain' => $domain]);

                json_response([
                    'success' => true,
                    'status' => 'dns_verified',
                    'message' => 'DNS doğrulandı. Sertifika ve yönlendirme birkaç dakika içinde otomatik olarak etkinleştirilecek.',
                ]);
                return;
            }

            $problems = [];
            if (!$txt_ok) {
                $problems[] = 'TXT kaydı (_ki-verify.' . $domain . ') bulunamadı veya değeri eşleşmiyor.';
            }
            if (!$target_ok) {
                $problems[] = 'CNAME/A kaydı henüz sunucumuzu (' . self::canonical_cname_target() . ') göstermiyor.';
            }
            $message = implode(' ', $problems) . ' DNS değişiklikleri yayılana kadar 5-30 dakika sürebilir.';

            $this->update_master_tenant(['custom_domain_last_error' => mb_substr($message, 0, 255)]);

            json_response(['success' => false, 'status' => $row['custom_domain_status'], 'message' => $message]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Cancel a domain that hasn't gone live yet. Deliberately refuses once the domain is 'active' -
     * tearing down a working custom domain (nginx server block + cert) is a real infrastructure
     * action, not just a settings row, so that stays a manual/support operation.
     */
    public function cancel_domain(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $row = $this->master_tenant_row();

            if (!$row || $row['custom_domain_status'] === 'none') {
                json_response(['success' => true]);
                return;
            }

            if ($row['custom_domain_status'] === 'active') {
                throw new InvalidArgumentException(
                    'Aktif bir özel alan adını kaldırmak için lütfen destek ile iletişime geçin.',
                );
            }

            $this->update_master_tenant([
                'custom_domain_pending' => null,
                'custom_domain_status' => 'none',
                'custom_domain_verification_token' => null,
                'custom_domain_requested_at' => null,
                'custom_domain_verified_at' => null,
                'custom_domain_last_error' => null,
            ]);

            audit_log('custom_domain.cancelled', 'tenant', (int) $row['id']);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Lightweight polling endpoint - the page calls this every few seconds while status is
     * 'dns_verified' or 'provisioning', waiting for scripts/domain-worker.sh to finish.
     */
    public function status(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $row = $this->master_tenant_row();

            json_response([
                'success' => true,
                'status' => $row['custom_domain_status'] ?? 'none',
                'domain' => $row['custom_domain'] ?? null,
                'pending_domain' => $row['custom_domain_pending'] ?? null,
                'last_error' => $row['custom_domain_last_error'] ?? null,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * @param string $host FQDN to look up (e.g. "_ki-verify.rezervasyon.firmaniz.com").
     * @param string $expected_value The exact TXT value we're expecting.
     */
    private function dns_txt_matches(string $host, string $expected_value): bool
    {
        $records = @dns_get_record($host, DNS_TXT);

        if (!$records) {
            return false;
        }

        foreach ($records as $record) {
            if (($record['txt'] ?? '') === $expected_value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $domain's CNAME (preferred) or A record already points at this deployment - the same
     * check scripts/add-custom-domain.sh does with `dig`, just via PHP so it can run inside a web
     * request instead of shelling out.
     */
    private function dns_points_here(string $domain): bool
    {
        $cname_records = @dns_get_record($domain, DNS_CNAME);

        foreach ($cname_records ?: [] as $record) {
            $target = rtrim((string) ($record['target'] ?? ''), '.');
            if (strtolower($target) === self::canonical_cname_target()) {
                return true;
            }
        }

        $a_records = @dns_get_record($domain, DNS_A);

        foreach ($a_records ?: [] as $record) {
            if (($record['ip'] ?? '') === self::CANONICAL_SERVER_IP) {
                return true;
            }
        }

        return false;
    }
}
