<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use Reports;

/**
 * Integration tests for Reports controller and analytics endpoints.
 */
class ReportsIntegrationTest extends TenantTestCase
{
    private Reports $reports;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('reports_model');
        self::ci()->load->model('providers_model');
        self::ci()->load->model('appointments_model');
        self::ci()->load->library('Google_sheets_fields');
    }

    public function test_method_helper_in_cli_and_post_get(): void
    {
        // Should not throw in CLI
        method('post');
        method('get');
        method('post|get');
        method('get|post');
        $this->assertTrue(true);
    }

    public function test_reports_model_get_revenue_rows(): void
    {
        $rows = self::ci()->reports_model->get_revenue_rows(date('Y-m-01'), date('Y-m-d'));
        $this->assertIsArray($rows);

        foreach ($rows as $row) {
            $metrics = self::ci()->reports_model->compute_row_metrics($row);
            $this->assertArrayHasKey('minutes', $metrics);
            $this->assertArrayHasKey('price', $metrics);
            $this->assertArrayHasKey('payout', $metrics);
            $this->assertIsNumeric($metrics['minutes']);
            $this->assertIsFloat($metrics['price']);
            $this->assertIsFloat($metrics['payout']);
        }
    }

    public function test_reports_model_calculate_available_minutes(): void
    {
        $providers = self::ci()->providers_model->get();
        if (!empty($providers)) {
            $pid = (int) $providers[0]['id'];
            $minutes = self::ci()->reports_model->calculate_available_minutes($pid, date('Y-m-01'), date('Y-m-d'));
            $this->assertGreaterThanOrEqual(0, $minutes);
        } else {
            $this->assertTrue(true);
        }
    }


    public function test_field_catalog_covers_all_template_columns(): void
    {
        $catalog = \Google_sheets_fields::catalog('appointments');
        $this->assertNotEmpty($catalog);

        $templates = [
            'aylik_muhasebe' => 'date,time,appointment_id,customer_name,service_name,effective_price,payment_status,payment_method,payment_amount,payment_balance,is_invoiced',
            'personel_prim' => 'date,provider_name,service_name,customer_name,effective_minutes,service_list_price,payout,early_exit_justification',
            'hizmet_karlilik' => 'date,service_name,provider_name,service_list_price,effective_price,effective_minutes,payment_status',
            'odeme_tahsilat' => 'date,time,customer_name,payment_method,payment_status,payment_amount,payment_balance,is_invoiced',
            'gun_sonu' => 'appointment_id,time,customer_name,customer_phone,provider_name,service_name,service_list_price,payment_status,payment_method,payment_amount,is_invoiced',
        ];

        foreach ($templates as $tmpl => $columnsStr) {
            $cols = explode(',', $columnsStr);
            foreach ($cols as $col) {
                $this->assertArrayHasKey($col, $catalog, "Column '$col' used in template '$tmpl' must exist in field catalog");
            }
        }
    }
}
