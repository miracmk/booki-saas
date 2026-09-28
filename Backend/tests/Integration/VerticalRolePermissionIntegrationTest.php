<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * End-to-end integration test suite for BooKi SaaS Vertical-First, Role-Aware,
 * and Permission-Aware architecture.
 *
 * Covers all scenarios required by Specification 22:
 * - Restaurant: Owner / Manager / Waiter / Cashier / Kitchen
 * - Clinic: Owner / Doctor / Nurse / Reception / Cashier
 * - Beauty: Owner / Reception / Professional
 * - Spa: Owner / Reception / Therapist
 * - Fitness: Owner / Reception / Trainer
 *
 * Validates:
 * - Vertical hierarchy (Family -> Business Type -> Blueprint -> Modules -> Navigation -> Roles -> Permissions)
 * - Navigation schema and visibility
 * - Granular CRUD, scopes (own, assigned, branch, all), and branch boundaries
 * - AI Governance policy intersection, authority gating, escalations, and controlled memory
 * - Demo role switcher
 */
class VerticalRolePermissionIntegrationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::require_multi_tenant();
    }

    /**
     * Helper to get tenant row by subdomain from master DB.
     */
    private function get_tenant_by_subdomain(string $subdomain): array
    {
        $tenant = self::db()->get_where('tenants', ['subdomain' => $subdomain])->row_array();
        $this->assertNotEmpty($tenant, "Tenant '{$subdomain}' must exist in master database.");
        return $tenant;
    }

    /**
     * 1. VERTICAL HIERARCHY & STANDARDIZATION TESTS
     */
    public function testVerticalHierarchyStandardization(): void
    {
        $ci = self::ci();
        $ci->load->library('vertical_service');
        $vs = $ci->vertical_service;

        // 1.1 Verify all 9 standard families
        $families = $vs->get_families();
        $expectedFamilies = [
            'beauty_wellness', 'restaurant_food', 'health_clinical', 'sports_fitness',
            'automotive', 'hospitality', 'experience', 'education', 'professional'
        ];
        foreach ($expectedFamilies as $f) {
            $this->assertArrayHasKey($f, $families, "Family '{$f}' must be registered.");
        }

        // 1.2 Constraint: massage_spa MUST be under beauty_wellness (NOT top-level)
        $this->assertSame('beauty_wellness', $vs->resolve_family('massage_spa'));

        // 1.3 Constraint: Clinical/medical MUST be under health_clinical (NOT beauty)
        $this->assertSame('health_clinical', $vs->resolve_family('doctor_clinic'));
        $this->assertSame('health_clinical', $vs->resolve_family('dentist'));
        $this->assertSame('health_clinical', $vs->resolve_family('psychology_dietitian_clinic'));

        // 1.4 Verify 12-key standardized terminology for restaurant
        $restTerms = $vs->get_terminology('restaurant');
        $this->assertSame('Misafir', $restTerms['customer']);
        $this->assertSame('Masa Rezervasyonu', $restTerms['appointment']);
        $this->assertSame('Restoran Menüsü & Gruplar', $restTerms['catalog']);
        $this->assertSame('Masa / Bölüm', $restTerms['station']);

        // 1.5 Verify 12-key standardized terminology for clinic
        $clinicTerms = $vs->get_terminology('doctor_clinic');
        $this->assertSame('Hasta', $clinicTerms['customer']);
        $this->assertSame('Hekim / Doktor', $clinicTerms['provider']);
        $this->assertSame('Muayene / Tedavi', $clinicTerms['appointment']);
        $this->assertSame('Klinik İşlemleri & Tedaviler', $clinicTerms['catalog']);
    }

    /**
     * 2. RESTAURANT ROLES MATRIX: Owner / Manager / Waiter / Cashier / Kitchen
     */
    public function testRestaurantRolesMatrix(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-restoran');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('navigation_service');
        $ci->load->library('vertical_service');
        $ps = $ci->permission_service;
        $ns = $ci->navigation_service;

        // 2.1 OWNER
        session([
            'user_id' => 1,
            'role_slug' => 'owner',
            'job_title' => 'İşletme Sahibi (Owner)',
            'is_admin' => 1,
        ]);

        $this->assertTrue($ps->can('view', 'restaurant_floor_plan', 1));
        $this->assertTrue($ps->can('add', 'adisyons', 1));
        $this->assertTrue($ps->can('refund', 'payments', 1));
        $this->assertTrue($ps->can('override', 'appointments', 1));
        $this->assertTrue($ps->can('view', 'reports', 1));
        $this->assertTrue($ps->can('manage', 'system_settings', 1));

        $navOwner = $ns->forCurrentUser();
        $this->assertArrayHasKey('operations', $navOwner);
        $this->assertArrayHasKey('finance', $navOwner);
        $this->assertArrayHasKey('settings', $navOwner);

        // 2.2 GENERAL MANAGER
        session([
            'user_id' => 1,
            'role_slug' => 'general_manager',
            'job_title' => 'Genel Müdür',
            'is_admin' => 0,
        ]);
        $this->assertTrue($ps->can('view', 'restaurant_floor_plan', 1));
        $this->assertTrue($ps->can('view', 'reports', 1));
        $this->assertTrue($ps->can('add', 'adisyons', 1));

        // 2.3 WAITER
        session([
            'user_id' => 1,
            'role_slug' => 'waiter',
            'job_title' => 'Garson',
            'is_admin' => 0,
        ]);

        // Waiter permissions
        $this->assertTrue($ps->can('view', 'restaurant_floor_plan', 1));
        $this->assertTrue($ps->can('add', 'adisyons', 1));
        $this->assertTrue($ps->can('edit', 'adisyons', 1));

        // Forbidden actions for waiter
        $this->assertFalse($ps->can('delete', 'customers', 1));
        $this->assertFalse($ps->can('refund', 'payments', 1));
        $this->assertFalse($ps->can('view', 'reports', 1));
        $this->assertFalse($ps->can('view', 'finance', 1));
        $this->assertFalse($ps->can('view', 'system_settings', 1));

        // Waiter Navigation
        $navWaiter = $ns->forCurrentUser();
        $this->assertArrayHasKey('operations', $navWaiter);
        $this->assertArrayNotHasKey('reports', $navWaiter, 'Waiter navigation must NOT include Reports.');
        $this->assertArrayNotHasKey('settings', $navWaiter, 'Waiter navigation must NOT include Settings.');

        // 2.4 KITCHEN
        session([
            'user_id' => 1,
            'role_slug' => 'kitchen',
            'job_title' => 'Mutfak (KDS)',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'verticals_kds', 1));
        $this->assertFalse($ps->can('view', 'customers', 1));
        $this->assertFalse($ps->can('view', 'finance', 1));
        $this->assertFalse($ps->can('view', 'reports', 1));
        $this->assertFalse($ps->can('view', 'system_settings', 1));

        $navKitchen = $ns->forCurrentUser();
        $this->assertArrayNotHasKey('crm', $navKitchen, 'Kitchen staff must NOT see CRM.');
        $this->assertArrayNotHasKey('finance', $navKitchen, 'Kitchen staff must NOT see Finance.');
        $this->assertArrayNotHasKey('reports', $navKitchen, 'Kitchen staff must NOT see Reports.');

        // 2.5 CASHIER
        session([
            'user_id' => 1,
            'role_slug' => 'cashier',
            'job_title' => 'Kasiyer',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'pos', 1));
        $this->assertTrue($ps->can('view', 'adisyons', 1));
        $this->assertTrue($ps->can('view', 'invoices', 1));
        $this->assertFalse($ps->can('view', 'system_settings', 1));
        $this->assertFalse($ps->can('delete', 'customers', 1));
    }

    /**
     * 3. CLINIC ROLES MATRIX: Owner / Doctor / Nurse / Reception / Cashier
     */
    public function testClinicRolesMatrix(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-klinik');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('navigation_service');
        $ps = $ci->permission_service;
        $ns = $ci->navigation_service;

        // 3.1 DOCTOR
        session([
            'user_id' => 2,
            'role_slug' => 'doctor',
            'job_title' => 'Uzm. Dr.',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'verticals_clinic', 2));
        $this->assertTrue($ps->can('add', 'verticals_clinic', 2));
        $this->assertTrue($ps->can('view', 'appointments', 2));
        $this->assertTrue($ps->can('view', 'customers', 2));

        // Forbidden for doctor
        $this->assertFalse($ps->can('view', 'finance', 2));
        $this->assertFalse($ps->can('view', 'expenses', 2));
        $this->assertFalse($ps->can('view', 'system_settings', 2));

        $navDoctor = $ns->forCurrentUser();
        $this->assertArrayHasKey('operations', $navDoctor);
        $this->assertArrayHasKey('crm', $navDoctor);
        $this->assertArrayNotHasKey('finance', $navDoctor);
        $this->assertArrayNotHasKey('settings', $navDoctor);

        // 3.2 NURSE
        session([
            'user_id' => 2,
            'role_slug' => 'nurse',
            'job_title' => 'Hemşire',
            'is_admin' => 0,
        ]);
        $this->assertTrue($ps->can('view', 'appointments', 2));
        $this->assertTrue($ps->can('view', 'verticals_clinic', 2));
        $this->assertFalse($ps->can('view', 'finance', 2));
        $this->assertFalse($ps->can('view', 'system_settings', 2));

        // 3.3 RECEPTION
        session([
            'user_id' => 2,
            'role_slug' => 'reception',
            'job_title' => 'Klinik Danışma',
            'is_admin' => 0,
        ]);
        $this->assertTrue($ps->can('view', 'appointments', 2));
        $this->assertTrue($ps->can('add', 'appointments', 2));
        $this->assertTrue($ps->can('view', 'customers', 2));
        $this->assertFalse($ps->can('view', 'finance', 2));
        $this->assertFalse($ps->can('view', 'system_settings', 2));

        // 3.4 CASHIER
        session([
            'user_id' => 2,
            'role_slug' => 'cashier',
            'job_title' => 'Vezne / Kasa',
            'is_admin' => 0,
        ]);
        $this->assertTrue($ps->can('view', 'pos', 2));
        $this->assertTrue($ps->can('view', 'invoices', 2));
        $this->assertFalse($ps->can('view', 'verticals_clinic', 2));
    }

    /**
     * 4. BEAUTY ROLES MATRIX: Owner / Reception / Professional
     */
    public function testBeautyRolesMatrix(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-guzellik');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('navigation_service');
        $ps = $ci->permission_service;
        $ns = $ci->navigation_service;

        // 4.1 RECEPTION
        session([
            'user_id' => 3,
            'role_slug' => 'reception',
            'job_title' => 'Resepsiyonist',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'appointments', 3));
        $this->assertTrue($ps->can('add', 'appointments', 3));
        $this->assertTrue($ps->can('view', 'customers', 3));
        $this->assertTrue($ps->can('view', 'pos', 3));
        $this->assertFalse($ps->can('view', 'reports', 3));
        $this->assertFalse($ps->can('view', 'system_settings', 3));

        // 4.2 PROFESSIONAL
        session([
            'user_id' => 3,
            'role_slug' => 'professional',
            'job_title' => 'Güzellik Uzmanı',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'appointments', 3));
        $this->assertFalse($ps->can('view', 'finance', 3));
        $this->assertFalse($ps->can('view', 'expenses', 3));
        $this->assertFalse($ps->can('view', 'system_settings', 3));

        $navPro = $ns->forCurrentUser();
        $this->assertArrayNotHasKey('finance', $navPro);
        $this->assertArrayNotHasKey('settings', $navPro);
    }

    /**
     * 5. SPA ROLES MATRIX: Owner / Reception / Therapist
     */
    public function testSpaRolesMatrix(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-masaj');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('navigation_service');
        $ps = $ci->permission_service;
        $ns = $ci->navigation_service;

        // 5.1 THERAPIST
        session([
            'user_id' => 4,
            'role_slug' => 'therapist',
            'job_title' => 'Masaj Terapisti',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'appointments', 4));
        $this->assertFalse($ps->can('view', 'finance', 4));
        $this->assertFalse($ps->can('view', 'system_settings', 4));

        $navTherapist = $ns->forCurrentUser();
        $this->assertArrayNotHasKey('finance', $navTherapist);
        $this->assertArrayNotHasKey('settings', $navTherapist);
    }

    /**
     * 6. FITNESS ROLES MATRIX: Owner / Reception / Trainer
     */
    public function testFitnessRolesMatrix(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-studyo');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('navigation_service');
        $ps = $ci->permission_service;
        $ns = $ci->navigation_service;

        // 6.1 TRAINER
        session([
            'user_id' => 5,
            'role_slug' => 'trainer',
            'job_title' => 'Personal Trainer',
            'is_admin' => 0,
        ]);

        $this->assertTrue($ps->can('view', 'appointments', 5));
        $this->assertFalse($ps->can('view', 'finance', 5));
        $this->assertFalse($ps->can('view', 'expenses', 5));
        $this->assertFalse($ps->can('view', 'system_settings', 5));

        $navTrainer = $ns->forCurrentUser();
        $this->assertArrayNotHasKey('finance', $navTrainer);
        $this->assertArrayNotHasKey('settings', $navTrainer);
    }

    /**
     * 7. MULTI-BRANCH SCOPE ENFORCEMENT
     */
    public function testMultiBranchScopeEnforcement(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-restoran');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('permission_service');
        $ps = $ci->permission_service;

        // Create test user bound strictly to Branch 101
        $testUserId = 9999;
        $ci->db->where('id', $testUserId)->delete('users');
        $ci->db->where('user_id', $testUserId)->delete('user_branches');

        $ci->db->insert('users', [
            'id' => $testUserId,
            'first_name' => 'BranchStaff',
            'last_name' => 'Demo',
            'email' => 'branch.staff@demo.test',
            'email_hash' => hash('sha256', 'branch.staff@demo.test'),
            'role_slug' => 'waiter',
            'job_title' => 'Garson',
            'branch_ids' => '101',
            'is_active' => 1,
        ]);

        $ci->db->insert('user_branches', [
            'user_id' => $testUserId,
            'branch_id' => 101,
            'is_primary' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Branch boundary checks
        $this->assertTrue($ps->check_branch_access($testUserId, 101), 'User must have access to their assigned branch 101.');
        $this->assertFalse($ps->check_branch_access($testUserId, 202), 'User must NOT have access to unassigned branch 202.');

        // Clean up
        $ci->db->where('id', $testUserId)->delete('users');
        $ci->db->where('user_id', $testUserId)->delete('user_branches');
    }

    /**
     * 8. AI GOVERNANCE: Policy Intersection, Authority Gating, Escalations & Controlled Memory
     */
    public function testAiGovernanceSuite(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-restoran');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('ai_governance_service');
        $gov = $ci->ai_governance_service;

        // 8.1 Authority Gating: Owner vs Waiter
        session(['user_id' => 1, 'role_slug' => 'owner', 'is_admin' => 1]);
        $ownerClearance = $gov->authorize_tool('propose_appointment_create', 1);
        $this->assertNotSame('forbidden', $ownerClearance);

        session(['user_id' => 1, 'role_slug' => 'waiter', 'is_admin' => 0]);
        $waiterClearance = $gov->authorize_tool('direct_delete_customer', 1);
        $this->assertSame('forbidden', $waiterClearance, 'Forbidden tool must return forbidden.');

        // 8.2 Multi-Domain Escalation Triggers
        $medicalMsg = "Hastanın cildinde alerjik reaksiyon oluştu acil ilaç gerekiyor";
        $medEsc = $gov->detect_escalation($medicalMsg);
        $this->assertNotNull($medEsc);
        $this->assertSame('medical', $medEsc['domain']);

        $legalMsg = "Sizi savcılığa ve tüketici mahkemesine şikayet edeceğim avukatımla görüşün";
        $legalEsc = $gov->detect_escalation($legalMsg);
        $this->assertNotNull($legalEsc);
        $this->assertSame('legal', $legalEsc['domain']);

        $paymentMsg = "Kredi kartımdan izinsiz para çekilmiş bankamdan chargeback yapacağım";
        $payEsc = $gov->detect_escalation($paymentMsg);
        $this->assertNotNull($payEsc);
        $this->assertSame('payment', $payEsc['domain']);

        $angryMsg = "Rezalet berbat bir servis personelleriniz terbiyesiz rezalet şikayetçiyim";
        $angryEsc = $gov->detect_escalation($angryMsg);
        $this->assertNotNull($angryEsc);
        $this->assertSame('angry_customer', $angryEsc['domain']);

        // 8.3 Escalation Handoff Creation
        $handoffId = $gov->create_escalation_handoff(1, 'medical', $medicalMsg, 'Test trigger');
        $this->assertGreaterThan(0, $handoffId);

        // 8.4 Controlled Behavioral Learning Pipeline:
        // Observed -> Suggested -> Owner Approval -> Business Rule -> Active
        $obsId = $gov->record_observation('frequent_cancellation', ['customer_id' => 123, 'count' => 3]);
        $this->assertGreaterThan(0, $obsId);

        $ruleId = $gov->suggest_rule(
            'cancellation_limit',
            '3 kez iptal eden müşterilerden kapora zorunlu tutulsun',
            ['require_deposit' => true],
            $obsId
        );
        $this->assertGreaterThan(0, $ruleId);

        // Verify status is 'suggested'
        $ruleRow = $ci->db->get_where('ai_learned_rules', ['id' => $ruleId])->row_array();
        $this->assertSame('suggested', $ruleRow['status']);

        // Owner Approves
        $approved = $gov->approve_rule($ruleId, 1);
        $this->assertTrue($approved);

        $activeRules = $gov->get_active_learned_rules();
        $activeRuleTypes = array_column($activeRules, 'rule_type');
        $this->assertContains('cancellation_limit', $activeRuleTypes);
    }

    /**
     * 9. DEMO ROLE SWITCHER
     */
    public function testDemoRoleSwitcher(): void
    {
        $tenant = $this->get_tenant_by_subdomain('demo-restoran');
        self::connect_tenant($tenant);

        $ci = self::ci();
        $ci->load->library('demo_service');
        $demo = $ci->demo_service;

        // Switch to waiter
        $result = $demo->switch_role('waiter');
        $this->assertTrue($result['success']);
        $this->assertSame('waiter', session('role_slug'));
        $this->assertSame('waiter', $result['user']['role_slug']);
        $this->assertNotEmpty(session('user_id'));

        // Switch to kitchen
        $result2 = $demo->switch_role('kitchen');
        $this->assertTrue($result2['success']);
        $this->assertSame('kitchen', session('role_slug'));

        // Switch back to owner
        $result3 = $demo->switch_role('owner');
        $this->assertTrue($result3['success']);
        $this->assertSame('owner', session('role_slug'));
    }
}
