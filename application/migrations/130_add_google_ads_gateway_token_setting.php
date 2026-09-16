<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Ads Gateway Token setting (Dalga 3 / Faz 3.1 placeholder).
 *
 * Adds google_ads_gateway_token to the ea_settings table for future Google Ads
 * integration. Settings are tenant-scoped.
 * -------------------------------------------------------------------------- */

class Migration_Add_google_ads_gateway_token_setting extends CI_Migration
{
    public function up(): void
    {
        $row = $this->db
            ->where('name', 'google_ads_gateway_token')
            ->get('settings')
            ->row_array();

        if ($row === null) {
            $this->db->insert('settings', [
                'name'  => 'google_ads_gateway_token',
                'value' => '',
            ]);
        }
    }

    public function down(): void
    {
        $this->db->where('name', 'google_ads_gateway_token')->delete('settings');
    }
}
