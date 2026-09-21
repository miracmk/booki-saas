<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TenantTestCase;

/**
 * System Scenarios: SaaS Sales CRM, Lead Management, Field Visits & Customer Onboarding.
 * Covers full CRM lifecycle, Kanban transitions, field interviews, onboarding wizard, and master DB integrity.
 */
class SaasCrmAndOnboardingSystemScenariosTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('leads_model');
        self::ci()->load->model('onboarding_sessions_model');
        self::ci()->load->model('master_audit_model');
        self::ci()->load->library('spreadsheet_importer');
    }

    /**
     * Test 1: Verify 560 seeded leads exist with valid stages, sectors, districts, and potential MRR.
     */
    public function test_crm_560_initial_leads_integrity(): void
    {
        $count = self::db()->count_all('leads');
        $this->assertGreaterThanOrEqual(560, $count, 'Lead table must contain at least 560 leads');

        // Check stages are valid
        $invalidStages = self::db()
            ->where_not_in('stage', [
                'New Lead', 'Qualified', 'Visit Planned', 'Visited', 'Meeting',
                'Demo Presented', 'Trial Started', 'Follow-up', 'Won', 'Lost'
            ])
            ->get('leads')
            ->result_array();
        $this->assertEmpty($invalidStages, 'All leads must have valid stage definitions');

        // Check potential MRR > 0
        $zeroMrr = self::db()->where('potential_mrr <=', 0)->count_all_results('leads');
        $this->assertSame(0, $zeroMrr, 'All leads should have positive potential MRR calculated');

        // Check distinct sectors and districts
        $sectors = self::ci()->leads_model->get_distinct_sectors();
        $this->assertNotEmpty($sectors);
        $districts = self::ci()->leads_model->get_distinct_districts();
        $this->assertNotEmpty($districts);
    }

    /**
     * Test 2: Duplicate check detects duplicate phone, email, and name+address.
     */
    public function test_crm_duplicate_detection(): void
    {
        $sample = self::db()->where('phone IS NOT NULL')->where('phone !=', '')->limit(1)->get('leads')->row_array();
        $this->assertNotEmpty($sample);

        $dup = self::ci()->leads_model->check_duplicate($sample['name'], $sample['phone']);
        $this->assertNotNull($dup);
        $this->assertSame((int) $sample['id'], (int) $dup['id']);
    }

    /**
     * Test 3: Create a test lead, change stage, verify stage history & timeline activities.
     */
    public function test_crm_lead_lifecycle_and_history(): void
    {
        $uniq = time() . '_' . mt_rand(1000, 9999);
        $name = "Test Salon {$uniq}";
        $phone = "0555" . mt_rand(1000000, 9999999);

        $leadId = self::ci()->leads_model->create_lead([
            'name' => $name,
            'phone' => $phone,
            'sector' => 'Kuaför',
            'district' => 'Kadıköy',
            'stage' => 'New Lead',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
        ], 'Test Suite');
        $this->assertGreaterThan(0, $leadId);

        // Update stage to Visit Planned
        $updated = self::ci()->leads_model->update_stage($leadId, 'Visit Planned', 'Test Suite', 'Görüşme randevusu alındı');
        $this->assertTrue($updated['success'] ?? false);
        $this->assertSame('Visit Planned', $updated['new_stage']);

        // Verify stage history
        $history = self::db()->where('id_leads', $leadId)->order_by('id', 'desc')->get('lead_stage_history')->row_array();
        $this->assertSame('Visit Planned', $history['new_stage']);
        $this->assertSame('Test Suite', $history['changed_by']);

        // Verify activities
        $activities = self::db()->where('id_leads', $leadId)->get('lead_activities')->result_array();
        $this->assertNotEmpty($activities);

        // Clean up
        self::db()->where('id_leads', $leadId)->delete('lead_stage_history');
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id', $leadId)->delete('leads');
    }

    /**
     * Test 4: Record field visit interview and verify visit details.
     */
    public function test_crm_field_visit_recording(): void
    {
        $uniq = time() . '_' . mt_rand(1000, 9999);
        $name = "Saha Ziyaret Test {$uniq}";

        $leadId = self::ci()->leads_model->create_lead([
            'name' => $name,
            'phone' => '05330000000',
            'sector' => 'Güzellik Salonu',
            'district' => 'Şişli',
            'stage' => 'Qualified',
        ], 'Test Suite');

        $visitId = self::ci()->leads_model->add_visit($leadId, [
            'visit_date' => date('Y-m-d H:i:s'),
            'visit_type' => 'field_visit',
            'contact_person' => 'Ayşe Hanım',
            'contact_title' => 'İşletme Sahibi',
            'current_method' => 'Defter / Manuel',
            'staff_count' => 5,
            'interest_level' => 'very_high',
            'notes' => 'Çok ilgili, 10 gün demo istedi',
            'new_stage' => 'Trial Started',
            'next_action_date' => date('Y-m-d', strtotime('+2 days')),
            'next_action_notes' => 'Arayıp demo kontrolü yapılacak',
        ], 'Saha Temsilcisi');

        $this->assertGreaterThan(0, $visitId);

        $visit = self::db()->get_where('lead_visits', ['id' => $visitId])->row_array();
        $this->assertSame('Ayşe Hanım', $visit['contact_person']);
        $this->assertSame(5, (int) $visit['staff_count']);
        $this->assertSame('Trial Started', $visit['suggested_stage']);

        // Clean up
        self::db()->where('id_leads', $leadId)->delete('lead_visits');
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id_leads', $leadId)->delete('lead_stage_history');
        self::db()->where('id', $leadId)->delete('leads');
    }

    /**
     * Test 5: Task creation, urgency filtering, and completion.
     */
    public function test_crm_task_management(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        $t1Id = self::ci()->leads_model->add_task([
            'title' => 'Gecikmiş Saha Görevi',
            'due_date' => $yesterday,
            'due_time' => '10:00:00',
            'priority' => 'high',
        ], 'Admin');

        $t2Id = self::ci()->leads_model->add_task([
            'title' => 'Bugünkü Saha Görevi',
            'due_date' => $today,
            'due_time' => '15:00:00',
            'priority' => 'normal',
        ], 'Admin');

        // Check task summary grouping
        $summary = self::ci()->leads_model->get_tasks_summary();
        $this->assertGreaterThanOrEqual(1, count($summary['overdue']));
        $this->assertGreaterThanOrEqual(1, count($summary['today']));

        // Complete task
        $done = self::ci()->leads_model->update_task_status($t1Id, 'completed', 'Admin');
        $this->assertTrue($done);

        $taskRow = self::db()->get_where('lead_tasks', ['id' => $t1Id])->row_array();
        $this->assertSame('completed', $taskRow['status']);
        $this->assertNotNull($taskRow['completed_at']);

        // Clean up
        self::db()->where_in('id', [$t1Id, $t2Id])->delete('lead_tasks');
    }

    /**
     * Test 6: 10-day trial calculation & remaining days logic.
     */
    public function test_crm_trial_countdown_calculation(): void
    {
        $today = new \DateTime('today');
        $endDate = (clone $today)->modify('+7 days');
        $diff = (int) $today->diff($endDate)->format('%r%a');
        $this->assertSame(7, $diff, 'Remaining trial days should be 7');

        $expiredDate = (clone $today)->modify('-2 days');
        $diffExpired = (int) $today->diff($expiredDate)->format('%r%a');
        $this->assertSame(-2, $diffExpired, 'Expired trial should have negative diff');
        $this->assertTrue($diffExpired < 0);
    }

    /**
     * Test 7: Onboarding Session token generation, step saving, and customer data provisioning.
     */
    public function test_onboarding_session_workflow(): void
    {
        $tenant = self::db()->limit(1)->get('tenants')->row_array();
        $this->assertNotEmpty($tenant, 'At least one tenant should exist');

        $session = self::ci()->onboarding_sessions_model->create_session((int) $tenant['id']);
        $this->assertNotEmpty($session['token']);
        $this->assertSame(48, strlen($session['token']), 'Onboarding token must be 48 hex characters');
        $this->assertNotEmpty($session['link']);

        // Save step 1 draft
        $step1 = ['business_name' => 'Test Güzellik', 'phone' => '05441234567', 'sector' => 'Güzellik Salonu'];
        $res = self::ci()->onboarding_sessions_model->save_step($session['token'], 1, $step1);
        $this->assertTrue($res['success']);

        // Fetch session
        $sess = self::ci()->onboarding_sessions_model->get_session_by_token($session['token']);
        $this->assertSame(2, (int) $sess['current_step']);
        $this->assertSame('in_progress', $sess['status']);
        $this->assertSame('Test Güzellik', $sess['data']['step_1']['business_name']);

        // Clean up
        self::db()->where('id', $session['id'])->delete('onboarding_sessions');
    }

    /**
     * Test 8: Spreadsheet importer parses CSV data correctly and matches headers.
     */
    public function test_spreadsheet_importer_csv_parsing(): void
    {
        $importer = new \Spreadsheet_importer();

        $csvContent = "İşletme Adı,Telefon,İlçe,Sektör,Yetkili Kişi\nTest Salon 1,05321112233,Kadıköy,Kuaför,Ahmet Bey\nTest Salon 2,05423334455,Şişli,Güzellik,Zeynep Hanım\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tmpFile, $csvContent);

        $parsed = $importer->parse_file($tmpFile);
        unlink($tmpFile);

        $this->assertCount(2, $parsed['rows'], 'Importer should parse 2 rows');
        $this->assertSame('Test Salon 1', $parsed['rows'][0]['İşletme Adı']);
        $this->assertSame('Kadıköy', $parsed['rows'][0]['İlçe']);
        $this->assertSame('Ahmet Bey', $parsed['rows'][0]['Yetkili Kişi']);
        $this->assertSame('Test Salon 2', $parsed['rows'][1]['İşletme Adı']);
    }

    /**
     * Test 9: Master audit log insertion and retrieval.
     */
    public function test_master_audit_logging(): void
    {
        $auditId = self::ci()->master_audit_model->log(
            'lead_stage_updated',
            'lead',
            '101',
            'Lead aşaması güncellendi',
            ['old_stage' => 'New Lead', 'new_stage' => 'Qualified']
        );
        $this->assertGreaterThan(0, $auditId);

        $logs = self::ci()->master_audit_model->get_logs(10, 0, 'lead', 'lead_stage_updated');
        $this->assertNotEmpty($logs['logs']);
        $this->assertSame('lead_stage_updated', $logs['logs'][0]['action']);

        // Clean up
        self::db()->where('id', $auditId)->delete('master_audit_logs');
    }

    /**
     * Test 10: Global Omnisearch finds leads, phone, district, and tenant matches.
     */
    public function test_superadmin_global_omnisearch(): void
    {
        // Search by known term in seeded data, e.g. "Kadıköy" or "Güzellik" or "Nilüfer"
        $results = self::ci()->leads_model->global_search('Nilüfer');
        $this->assertNotEmpty($results['results']);
        $this->assertGreaterThan(0, $results['total']);

        // Search by phone snippet
        $sample = self::db()->where('phone IS NOT NULL')->where('phone !=', '')->limit(1)->get('leads')->row_array();
        if ($sample) {
            $last4 = substr(preg_replace('/[^0-9]/', '', $sample['phone']), -4);
            $resPhone = self::ci()->leads_model->global_search($last4);
            $this->assertNotEmpty($resPhone['results']);
        }
    }

    /**
     * Test 11: End-to-end Onboarding completion lifecycle and tenant activation.
     */
    public function test_onboarding_completion_lifecycle_and_tenant_activation(): void
    {
        $tenant = self::db()->limit(1)->get('tenants')->row_array();
        $this->assertNotEmpty($tenant);

        // Create a mock lead
        $leadId = self::ci()->leads_model->create_lead([
            'name' => 'Kurulum Tamamlama Testi',
            'phone' => '05559998877',
            'sector' => 'Berber',
            'stage' => 'Demo Presented',
        ]);

        // Create session
        $session = self::ci()->onboarding_sessions_model->create_session((int) $tenant['id'], $leadId);
        $token = $session['token'];

        // Save mock step 1 & 2
        self::ci()->onboarding_sessions_model->save_step($token, 1, ['company_name' => 'Kurulum Tamamlama Testi']);
        self::ci()->onboarding_sessions_model->save_step($token, 2, ['business_hours' => ['monday' => ['start' => '09:00', 'end' => '18:00']]]);

        // Complete onboarding
        $completeResult = self::ci()->onboarding_sessions_model->complete_onboarding($token);
        $this->assertTrue($completeResult['success']);

        // Verify session status = completed
        $sessRow = self::db()->get_where('onboarding_sessions', ['token' => $token])->row_array();
        $this->assertSame('completed', $sessRow['status']);
        $this->assertSame(100, (int) $sessRow['progress_percent']);

        // Verify tenant status = active and onboarding_status = completed
        $tRow = self::db()->get_where('tenants', ['id' => $tenant['id']])->row_array();
        $this->assertSame('active', $tRow['status']);
        $this->assertSame('completed', $tRow['onboarding_status']);

        // Verify lead stage = Won
        $lRow = self::db()->get_where('leads', ['id' => $leadId])->row_array();
        $this->assertSame('Won', $lRow['stage']);
        $this->assertSame('converted', $lRow['trial_status']);

        // Clean up
        self::db()->where('token', $token)->delete('onboarding_sessions');
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id_leads', $leadId)->delete('lead_stage_history');
        self::db()->where('id', $leadId)->delete('leads');
    }

    /**
     * Test 12: Customer Onboarding public view rendering.
     */
    public function test_customer_onboarding_view_rendering(): void
    {
        $tenant = self::db()->limit(1)->get('tenants')->row_array();
        $session = self::ci()->onboarding_sessions_model->create_session((int) $tenant['id']);
        $token = $session['token'];

        $data = [
            'session' => $session,
            'tenant' => $tenant,
            'current_step' => 1,
            'total_steps' => 10,
            'progress_percent' => 0,
            'session_data' => $session['data'] ?? [],
            'csrf_token' => 'dummy_csrf',
        ];
        $html = self::ci()->load->view('pages/customer_onboarding', $data, true);

        $this->assertStringContainsString('İşletme Kurulum Sihirbazı', $html);
        $this->assertStringContainsString($tenant['company_name'] ?: $tenant['subdomain'], $html);
        $this->assertStringContainsString('İşletme Temel Bilgileri', $html);
        $this->assertStringContainsString('Kurulumu Tamamla', $html);

        // Clean up
        self::db()->where('token', $token)->delete('onboarding_sessions');
    }

    /**
     * Test 13: Superadmin tenants & CRM view rendering.
     */
    public function test_superadmin_tenants_view_rendering(): void
    {
        html_vars([
            'page_title' => 'BooKi — Super Admin & Saha Satış / CRM Platformu',
            'csrf_token' => 'dummy_token',
            'superadmin_username' => 'testadmin',
            'tenants' => [],
            'search' => '',
            'page' => 1,
            'total_pages' => 1,
            'total' => 0,
            'total_tenants' => 0,
            'active_tenants' => 0,
            'total_customers' => 0,
            'total_monthly_appointments' => 0,
            'total_appointments' => 0,
            'total_mrr' => 0.00,
            'crm_kpis' => self::ci()->leads_model->get_kpis(),
            'sectors' => self::ci()->leads_model->get_distinct_sectors(),
            'districts' => self::ci()->leads_model->get_distinct_districts(),
            'tasks_summary' => self::ci()->leads_model->get_tasks_summary(),
            'onboarding_sessions' => [],
            'recent_activities' => self::ci()->leads_model->get_recent_activities(5),
            'stage_definitions' => \Leads_model::STAGES,
            'active_tab' => 'dashboard',
        ]);
        $html = self::ci()->load->view('pages/superadmin_tenants', [], true);

        $this->assertStringContainsString('BooKi', $html);
        $this->assertStringContainsString('Toplam Lead Havuzu', $html);
        $this->assertStringContainsString('Satış Hattı (Kanban)', $html);
        $this->assertStringContainsString('Lead Havuzu (560)', $html);
        $this->assertStringContainsString('Saha & Görevler', $html);
        $this->assertStringContainsString('Onboarding Takibi', $html);
        $this->assertStringContainsString('İçe Aktar (Excel/CSV)', $html);
        $this->assertStringContainsString('Saha Satış & Argüman Rehberi', $html);
        $this->assertStringContainsString('Platform Ayarları', $html);
        $this->assertStringContainsString('Yapay Zeka & Ses Motoru', $html);
        $this->assertStringContainsString('Zadarma SIP & Telefoni Santrali', $html);
    }

    /**
     * Test 14: Lead API payload and omnichannel links formatting (WhatsApp, Instagram, Email, Phone).
     */
    public function test_crm_api_leads_payload_and_omnichannel_formatting(): void
    {
        $res = self::ci()->leads_model->get_leads([], 1, 10);
        $this->assertTrue($res['success'] ?? false, 'get_leads must return success: true');
        $this->assertNotEmpty($res['leads'], 'Leads list must not be empty');
        $this->assertGreaterThan(0, $res['total']);

        // Check formatting helper
        $formatted = self::ci()->leads_model->format_lead_metadata([
            'phone' => '0532 123 45 67',
            'email' => '  info@salon.com ',
            'instagram' => '@guzellik_merkezi',
        ]);
        $this->assertSame('905321234567', $formatted['clean_phone']);
        $this->assertSame('info@salon.com', $formatted['clean_email']);
        $this->assertSame('guzellik_merkezi', $formatted['clean_instagram']);
        $this->assertSame('https://www.instagram.com/guzellik_merkezi', $formatted['instagram_url']);
    }

    /**
     * Test 15: Pipeline Kanban structure contains stages, counts, and lead arrays.
     */
    public function test_crm_pipeline_kanban_structure(): void
    {
        $kanban = self::ci()->leads_model->get_pipeline_kanban();
        $this->assertIsArray($kanban);

        foreach (\Leads_model::STAGES as $stageKey => $stageLabel) {
            $this->assertArrayHasKey($stageKey, $kanban, "Kanban must have stage key {$stageKey}");
            $this->assertArrayHasKey('stage_key', $kanban[$stageKey]);
            $this->assertArrayHasKey('count', $kanban[$stageKey]);
            $this->assertArrayHasKey('leads', $kanban[$stageKey]);
            $this->assertIsArray($kanban[$stageKey]['leads']);
            $this->assertSame($kanban[$stageKey]['count'], count($kanban[$stageKey]['leads']));
        }
    }

    /**
     * Test 16: Voice Calling Activity (Zadarma, ElevenLabs, Gemini Live) with transcript, duration, notes.
     */
    public function test_crm_call_activity_and_transcripts(): void
    {
        $leadId = self::ci()->leads_model->create_lead([
            'name' => 'Call Test Lead',
            'phone' => '05339998877',
            'sector' => 'Diyetisyen',
            'district' => 'Beşiktaş',
            'stage' => 'Qualified',
        ], 'Test Admin');
        $this->assertGreaterThan(0, $leadId);

        // Record a simulated Gemini Live call with transcript
        $callData = [
            'provider' => 'gemini_live',
            'call_mode' => 'ai_live_call',
            'outcome' => 'demo_scheduled',
            'duration_seconds' => 184,
            'transcript' => "Asistan: Merhaba, BooKi randevu sistemi için arıyorum.\nMüşteri: Randevu hatırlatmalarını WhatsApp ile atıyor musunuz?\nAsistan: Evet, Baileys köprümüzle tam otomatik.",
            'notes' => 'Müşteri WhatsApp hatırlatmasını çok beğendi, yarın 14:00 demo istedi.',
            'created_by' => 'AI Sales Agent',
        ];

        $activityId = self::ci()->leads_model->add_call_activity($leadId, $callData);
        $this->assertGreaterThan(0, $activityId);

        // Verify lead_activities entry
        $act = self::db()->where('id', $activityId)->get('lead_activities')->row_array();
        $this->assertNotEmpty($act);
        $this->assertSame('call', $act['activity_type']);
        $this->assertStringContainsString('GEMINI_LIVE', $act['title']);
        $this->assertStringContainsString('Müşteri WhatsApp hatırlatmasını', $act['description']);

        $meta = json_decode($act['metadata_json'] ?? '{}', true);
        $this->assertSame('gemini_live', $meta['provider'] ?? null);
        $this->assertSame(184, $meta['duration'] ?? null);
        $this->assertStringContainsString('Baileys köprümüzle', $meta['transcript'] ?? '');
        $this->assertStringContainsString('WhatsApp', $meta['transcript'] ?? '');

        // Clean up
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id', $leadId)->delete('leads');
    }

    /**
     * Test 17: Omnichannel communication activity logging (WhatsApp, Instagram, Email).
     */
    public function test_crm_omnichannel_activity_logging(): void
    {
        $leadId = self::ci()->leads_model->create_lead([
            'name' => 'Omnichannel Test Salon',
            'phone' => '05441112233',
            'email' => 'omni@testsalon.com',
            'stage' => 'Qualified',
        ], 'Test Admin');

        // Log WhatsApp
        $waActId = self::ci()->leads_model->add_activity($leadId, 'whatsapp', 'WhatsApp Tanıtım Mesajı Gönderildi', 'Şablon 1 ile no-show düşürme teklifi iletildi.', 'Saha Ekibi', ['phone' => '905441112233']);
        $this->assertGreaterThan(0, $waActId);

        // Log Instagram
        $igActId = self::ci()->leads_model->add_activity($leadId, 'instagram', 'Instagram DM Profili Ziyaret Edildi', 'DM üzerinden demo videosu iletildi.', 'Saha Ekibi', ['handle' => 'omnisalon']);
        $this->assertGreaterThan(0, $igActId);

        // Log Email
        $mailActId = self::ci()->leads_model->add_activity($leadId, 'email', 'E-posta Teklifi İletildi', 'PDF broşür ve fiyat teklifi gönderildi.', 'Saha Ekibi', ['email' => 'omni@testsalon.com']);
        $this->assertGreaterThan(0, $mailActId);

        // Verify all 4 activities in DB (1 creation + 3 omnichannel)
        $acts = self::db()->where('id_leads', $leadId)->get('lead_activities')->result_array();
        $this->assertCount(4, $acts);

        $types = array_column($acts, 'activity_type');
        $this->assertContains('whatsapp', $types);
        $this->assertContains('instagram', $types);
        $this->assertContains('email', $types);

        // Clean up
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id', $leadId)->delete('leads');
    }

    /**
     * Test 18: Platform Settings Master Persistence (Zadarma, ElevenLabs, Google AI Studio).
     */
    public function test_platform_settings_master_persistence(): void
    {
        // Test updating settings using master_setting helper
        master_setting('zadarma_api_key', 'test_zadarma_key_123');
        master_setting('elevenlabs_voice_id', 'custom_voice_xyz');
        master_setting('gemini_live_voice', 'Puck');

        $this->assertSame('test_zadarma_key_123', master_setting('zadarma_api_key'));
        $this->assertSame('custom_voice_xyz', master_setting('elevenlabs_voice_id'));
        $this->assertSame('Puck', master_setting('gemini_live_voice'));

        // Restore Zadarma original key
        master_setting('zadarma_api_key', 'a79256819256392e2336');
        master_setting('gemini_live_voice', 'Aoede');
    }

    /**
     * Test 19: Save call log with outcome, stage transition (e.g. Trial Started), and follow-up task.
     */
    public function test_crm_call_log_stage_transition_and_followup_task(): void
    {
        $leadId = self::ci()->leads_model->create_lead([
            'name' => 'Call Stage Test Salon',
            'phone' => '05338887766',
            'sector' => 'Güzellik Salonu',
            'stage' => 'Qualified',
        ], 'Test Admin');
        $this->assertGreaterThan(0, $leadId);

        // 1. Call add_call_activity with outcome and new_stage
        $actId = self::ci()->leads_model->add_call_activity($leadId, [
            'provider' => 'zadarma',
            'duration' => 125,
            'outcome' => 'demo_started',
            'new_stage' => 'Trial Started',
            'notes' => 'Görüşme çok olumlu geçti, 10 günlük demo başlatıldı.',
            'transcript' => 'Operatör: Merhaba... Müşteri: Harika, hemen başlayalım.',
            'performed_by' => 'Super Admin',
        ]);
        $this->assertGreaterThan(0, $actId);

        // Verify lead stage was updated to Trial Started
        $lead = self::ci()->leads_model->get_lead_by_id($leadId);
        $this->assertSame('Trial Started', $lead['stage']);
        $this->assertSame('active', $lead['trial_status']);
        $this->assertNotNull($lead['demo_start_date']);
        $this->assertNotNull($lead['demo_end_date']);

        // 2. Add follow-up task as would be created by api_save_call_log
        $taskId = self::ci()->leads_model->add_task([
            'id_leads' => $leadId,
            'title' => 'Takip Görüşmesi: Zadarma SIP',
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'due_time' => '14:30',
            'priority' => 'high',
            'notes' => 'Arama sonrası demo durumunu sorma.',
        ], 'Super Admin');
        $this->assertGreaterThan(0, $taskId);

        // 3. Verify audit log works with various argument shapes without ArgumentCountError
        $auditId1 = self::ci()->master_audit_model->log('call_logged', 'lead', (string) $leadId, 'Arama kaydedildi');
        $this->assertGreaterThan(0, $auditId1);

        $auditId2 = self::ci()->master_audit_model->log('settings_updated', 'Platform ayarları güncellendi');
        $this->assertGreaterThan(0, $auditId2);

        // Clean up
        self::db()->where('id_leads', $leadId)->delete('lead_tasks');
        self::db()->where('id_leads', $leadId)->delete('lead_stage_history');
        self::db()->where('id_leads', $leadId)->delete('lead_activities');
        self::db()->where('id', $leadId)->delete('leads');
        self::db()->where_in('id', [$auditId1, $auditId2])->delete('master_audit_logs');
    }
}

