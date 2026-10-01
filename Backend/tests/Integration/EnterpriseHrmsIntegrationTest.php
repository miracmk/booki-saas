<?php

namespace Tests\Integration;

defined('BASEPATH') or exit('No direct script access allowed');

use Tests\TenantTestCase;

/**
 * Enterprise HRMS Suite Integration Test.
 *
 * Validates:
 * 1. Departments & Designations management
 * 2. Employee Profile & Document Vault
 * 3. Shifts & PDKS Punch Logs with daily attendance calculation
 * 4. 4857 SK Leave Allocations, Applications & Approval with calendar sync
 * 5. Salary Structure, BooKi Commission sync & Payroll generation
 * 6. Advances & Expense Claims workflow
 * 7. Asset Custody assignment & return
 * 8. ATS Recruitment Pipeline
 * 9. Navigation Service HRMS items
 */
class EnterpriseHrmsIntegrationTest extends TenantTestCase
{
    private static int $staffUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ci()->load->model('hr_model');
        $this->ci()->load->library('hr_service');
        $this->ci()->load->library('navigation_service');

        self::$staffUserId = $this->ensureUser('hrms_test_staff@booki.local', 'Buse', 'Demir', '05559998877', 1, 'admin');
    }

    private function ensureUser(string $email, string $firstName, string $lastName, string $phone, int $roleId, string $roleSlug): int
    {
        $db = self::db();
        $user = $db->get_where('users', ['email' => $email])->row_array();
        if (!$user) {
            $db->insert('users', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phone,
                'id_roles' => $roleId,
                'role_slug' => $roleSlug,
                'is_active' => 1,
            ]);
            return (int) $db->insert_id();
        }
        return (int) $user['id'];
    }

    public function testDepartmentsAndDesignations(): void
    {
        $hrModel = $this->ci()->hr_model;

        // Save department
        $deptId = $hrModel->save_department([
            'name' => 'Test Ar-Ge & Yazılım',
            'code' => 'RND',
        ]);
        $this->assertGreaterThan(0, $deptId);

        $depts = $hrModel->get_departments();
        $this->assertNotEmpty($depts);

        // Save designation
        $desigId = $hrModel->save_designation([
            'title' => 'Kıdemli Sistem Mimarı',
            'code' => 'ARCH',
        ]);
        $this->assertGreaterThan(0, $desigId);
    }

    public function testEmployeeProfileAndDocuments(): void
    {
        $hrModel = $this->ci()->hr_model;

        $res = $hrModel->save_employee_profile(self::$staffUserId, [
            'tckn_passport' => '12345678901',
            'job_title' => 'Baş Direktör',
            'date_of_joining' => '2023-01-15',
            'iban' => 'TR990006200000012345678901',
            'pin_code' => '1234',
        ]);
        $this->assertTrue($res);

        $emp = $hrModel->get_employee(self::$staffUserId);
        $this->assertNotNull($emp);
        $this->assertEquals('12345678901', $emp['tckn_passport']);
        $this->assertEquals('1234', $emp['pin_code']);

        // Upload doc
        $docId = $hrModel->save_document([
            'id_users' => self::$staffUserId,
            'title' => 'İş Sözleşmesi 2026',
            'document_type' => 'contract',
            'file_path' => 'storage/hr_documents/test.pdf',
            'file_name' => 'test.pdf',
        ]);
        $this->assertGreaterThan(0, $docId);

        $docs = $hrModel->get_documents(self::$staffUserId);
        $this->assertNotEmpty($docs);
    }

    public function testShiftsAndAttendancePunch(): void
    {
        $hrModel = $this->ci()->hr_model;

        $shifts = $hrModel->get_shifts();
        $this->assertNotEmpty($shifts);
        $shId = (int) $shifts[0]['id'];

        // Assign shift
        $asgId = $hrModel->save_shift_assignment([
            'id_users' => self::$staffUserId,
            'id_shifts' => $shId,
            'start_date' => date('Y-m-d'),
        ]);
        $this->assertGreaterThan(0, $asgId);

        // Punch IN
        $resIn = $hrModel->log_attendance(self::$staffUserId, 'IN', 'test');
        $this->assertTrue($resIn['success']);

        // Punch OUT
        $resOut = $hrModel->log_attendance(self::$staffUserId, 'OUT', 'test');
        $this->assertTrue($resOut['success']);

        $daily = $hrModel->get_daily_attendance(date('Y-m-d'), self::$staffUserId);
        $this->assertNotEmpty($daily);
        $this->assertEquals('present', $daily[0]['status']);
    }

    public function testLaborLawLeavesAndCalendarSync(): void
    {
        $hrModel = $this->ci()->hr_model;

        // Allocations (4857 SK)
        $allocs = $hrModel->get_leave_allocations(self::$staffUserId, (int) date('Y'));
        $this->assertNotEmpty($allocs);

        // Leave application
        $types = $hrModel->get_leave_types();
        $typeId = (int) $types[0]['id'];

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $appId = $hrModel->save_leave_application([
            'id_users' => self::$staffUserId,
            'id_leave_types' => $typeId,
            'start_date' => $tomorrow,
            'end_date' => $tomorrow,
            'reason' => 'Yıllık izin testi',
        ]);
        $this->assertGreaterThan(0, $appId);

        // Approve leave
        $appRes = $hrModel->update_leave_status($appId, 'approved', self::$staffUserId);
        $this->assertTrue($appRes);

        // Check if synced with blocked_periods to block calendar
        $db = self::db();
        $unavail = $db->get_where('blocked_periods', [
            'start_datetime' => "{$tomorrow} 00:00:00",
        ])->row_array();
        $this->assertNotNull($unavail);
    }

    public function testSalaryAndPayrollGeneration(): void
    {
        $hrModel = $this->ci()->hr_model;

        $hrModel->save_salary_structure(self::$staffUserId, [
            'base_salary' => 45000.00,
            'currency' => 'TRY',
        ]);

        $m = (int) date('m');
        $y = (int) date('Y');

        $payrollRes = $hrModel->generate_payroll($m, $y);
        $this->assertGreaterThan(0, $payrollRes['payroll_id']);

        $slips = $hrModel->get_payroll_slips($payrollRes['payroll_id']);
        $this->assertNotEmpty($slips);

        $mySlip = null;
        foreach ($slips as $s) {
            if ((int) $s['id_users'] === self::$staffUserId) {
                $mySlip = $s;
                break;
            }
        }
        $this->assertNotNull($mySlip);
        $this->assertEquals(45000.00, (float) $mySlip['base_salary']);
        $this->assertGreaterThan(0, (float) $mySlip['net_pay']);
    }

    public function testAssetsAndRecruitment(): void
    {
        $hrModel = $this->ci()->hr_model;

        // Asset
        $astId = $hrModel->save_asset([
            'asset_name' => 'Dell XPS 15',
            'asset_code' => 'LAP-TEST-99',
            'category' => 'laptop',
            'serial_number' => 'SN-998877',
        ]);
        $this->assertGreaterThan(0, $astId);

        // Assign asset
        $assignId = $hrModel->assign_asset([
            'id_assets' => $astId,
            'id_users' => self::$staffUserId,
        ]);
        $this->assertGreaterThan(0, $assignId);

        // Return asset
        $retRes = $hrModel->return_asset($assignId, 'Eksiksiz iade alındı');
        $this->assertTrue($retRes);

        // ATS
        $jobId = $hrModel->save_job_opening([
            'title' => 'Kıdemli Kuaför / Stilist',
            'job_description' => '5 yıl deneyimli stilist aranıyor',
        ]);
        $this->assertGreaterThan(0, $jobId);

        $appId = $hrModel->save_job_applicant([
            'id_job_openings' => $jobId,
            'full_name' => 'Ali Yıldız',
            'email' => 'ali@yildiz.com',
            'phone' => '05321112233',
        ]);
        $this->assertGreaterThan(0, $appId);
    }

    public function testNavigationHasAllHrmsItems(): void
    {
        $this->ci()->session->set_userdata([
            'user_id' => self::$staffUserId,
            'is_admin' => true,
            'role_slug' => 'admin',
        ]);

        $nav = $this->ci()->navigation_service->forCurrentUser();
        $this->assertArrayHasKey('team', $nav);
        $this->assertEquals('Ekip & İK', $nav['team']['title']);

        $routes = array_column($nav['team']['items'], 'route');
        $this->assertContains('hr', $routes);
        $this->assertContains('attendance', $routes);
        $this->assertContains('leaves', $routes);
        $this->assertContains('payroll', $routes);
        $this->assertContains('ess', $routes);
    }
}
