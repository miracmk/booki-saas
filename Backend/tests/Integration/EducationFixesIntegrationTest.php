<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 8 (education - Özel Kurs, Sanat / Müzik Atölyesi, Sınıf / Seans / Yoklama)
 */
class EducationFixesIntegrationTest extends TenantTestCase
{
    private static int $instructorId;
    private static int $studentId;
    private static int $sessionId;
    private static int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();
        $db = self::db();

        // 1. Instructor
        $instructor = $db->get_where('users', ['email' => 'muzik_hocasi@booki.local'])->row_array();
        if (!$instructor) {
            $db->insert('users', [
                'first_name' => 'Kemal',
                'last_name' => 'Öğretmen',
                'email' => 'muzik_hocasi@booki.local',
                'phone_number' => '05559998877',
                'id_roles' => 2,
                'role_slug' => 'provider',
                'is_active' => 1,
            ]);
            self::$instructorId = (int) $db->insert_id();
        } else {
            self::$instructorId = (int) $instructor['id'];
        }

        // 2. Student
        $student = $db->get_where('users', ['email' => 'ogrenci_ali@booki.local'])->row_array();
        if (!$student) {
            $db->insert('users', [
                'first_name' => 'Ali',
                'last_name' => 'Öğrenci',
                'email' => 'ogrenci_ali@booki.local',
                'phone_number' => '05553332211',
                'id_roles' => 3,
                'role_slug' => 'customer',
                'is_active' => 1,
            ]);
            self::$studentId = (int) $db->insert_id();
        } else {
            self::$studentId = (int) $student['id'];
        }

        // 3. Service (Piyano Atölyesi)
        $service = $db->get_where('services', ['name' => 'Piyano Başlangıç Atölyesi'])->row_array();
        if (!$service) {
            $db->insert('services', [
                'name' => 'Piyano Başlangıç Atölyesi',
                'duration' => 60,
                'price' => 500.00,
            ]);
            self::$serviceId = (int) $db->insert_id();
        } else {
            self::$serviceId = (int) $service['id'];
        }

        // 4. Appointment / Session
        $session = $db->get_where('appointments', ['id_services' => self::$serviceId])->row_array();
        if (!$session) {
            $db->insert('appointments', [
                'id_services' => self::$serviceId,
                'id_users_provider' => self::$instructorId,
                'id_users_customer' => self::$studentId,
                'start_datetime' => date('Y-m-d 10:00:00'),
                'end_datetime' => date('Y-m-d 11:00:00'),
                'status' => 'Confirmed',
                'hash' => bin2hex(random_bytes(16)),
            ]);
            self::$sessionId = (int) $db->insert_id();
        } else {
            self::$sessionId = (int) $session['id'];
        }

        // Ensure migration 174 tables exist
        self::ci()->load->library('migration');
        self::ci()->migration->version(174);
    }

    private function createVerticalsController(): \Verticals
    {
        require_once APPPATH . 'controllers/Verticals.php';
        $ci = self::ci();
        $controller = (new \ReflectionClass(\Verticals::class))->newInstanceWithoutConstructor();
        $controller->props = &$ci->props;
        $controller->load = $ci->load;
        $controller->load->model('customers_model');
        $controller->load->model('roles_model');
        $controller->load->library('accounts');
        $controller->customers_model = $ci->customers_model;
        $controller->roles_model = $ci->roles_model;
        $controller->accounts = $ci->accounts;
        $controller->output = $ci->output;
        $controller->input = $ci->input;
        $controller->db = $ci->db;
        return $controller;
    }

    public function testAttendanceRecordingAndPackageSessionDeduction(): void
    {
        $db = self::db();

        // Give student an active package with 4 sessions total, 1 used
        $db->delete('customer_packages', ['id_users_customer' => self::$studentId]);
        $db->insert('customer_packages', [
            'id_users_customer' => self::$studentId,
            'id_services' => self::$serviceId,
            'total_sessions' => 4,
            'used_sessions' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $pkgId = (int) $db->insert_id();

        // Simulate save_attendance payload
        $_POST = [
            'session_id' => self::$sessionId,
            'records' => [
                [
                    'student_id' => self::$studentId,
                    'status' => 'present',
                    'notes' => 'Zamanında katıldı ve pratik yaptı.',
                ]
            ]
        ];

        session(['user_id' => self::$instructorId, 'role_slug' => 'provider']);
        $controller = $this->createVerticalsController();
        $controller->save_attendance();

        $response = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($response['success'], 'Attendance save should succeed.');
        $this->assertEquals(1, $response['updated_count']);

        // Check DB attendance record
        $att = $db->get_where('course_attendance', [
            'id_appointments' => self::$sessionId,
            'id_users_customer' => self::$studentId,
        ])->row_array();
        $this->assertNotNull($att);
        $this->assertEquals('present', $att['status']);
        $this->assertEquals('Zamanında katıldı ve pratik yaptı.', $att['notes']);

        // Check package session was decremented (used_sessions became 2)
        $pkg = $db->get_where('customer_packages', ['id' => $pkgId])->row_array();
        $this->assertEquals(2, (int) $pkg['used_sessions'], 'Present status should increment used_sessions by 1.');
    }

    public function testAttendanceAbsentDoesNotDeductPackage(): void
    {
        $db = self::db();

        // Give student active package
        $db->delete('customer_packages', ['id_users_customer' => self::$studentId]);
        $db->insert('customer_packages', [
            'id_users_customer' => self::$studentId,
            'id_services' => self::$serviceId,
            'total_sessions' => 5,
            'used_sessions' => 2,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $pkgId = (int) $db->insert_id();

        $_POST = [
            'session_id' => self::$sessionId,
            'records' => [
                [
                    'student_id' => self::$studentId,
                    'status' => 'absent',
                    'notes' => 'Derse mazeretsiz katılmadı.',
                ]
            ]
        ];

        session(['user_id' => self::$instructorId, 'role_slug' => 'provider']);
        $controller = $this->createVerticalsController();
        $controller->save_attendance();

        $response = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($response['success']);

        // Check used_sessions remained 2
        $pkg = $db->get_where('customer_packages', ['id' => $pkgId])->row_array();
        $this->assertEquals(2, (int) $pkg['used_sessions'], 'Absent status should not deduct package session.');
    }

    public function testStudentEvaluationAndGrading(): void
    {
        $db = self::db();

        $_POST = [
            'student_id' => self::$studentId,
            'session_id' => self::$sessionId,
            'subject' => 'Solfej & Armoni Sınavı',
            'grade_score' => 95.50,
            'feedback_notes' => 'Ritim kalıpları kusursuz, akor geçişleri çok başarılı.',
        ];

        session(['user_id' => self::$instructorId, 'role_slug' => 'provider']);
        $controller = $this->createVerticalsController();
        $controller->save_student_grade();

        $response = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($response['success'], 'Student evaluation save should succeed.');
        $this->assertGreaterThan(0, $response['evaluation_id']);

        // Check DB evaluation record
        $ev = $db->get_where('student_evaluations', ['id' => (int) $response['evaluation_id']])->row_array();
        $this->assertNotNull($ev);
        $this->assertEquals(self::$studentId, (int) $ev['id_users_customer']);
        $this->assertEquals('Solfej & Armoni Sınavı', $ev['subject']);
        $this->assertEquals(95.50, (float) $ev['grade_score']);
        $this->assertEquals('Ritim kalıpları kusursuz, akor geçişleri çok başarılı.', $ev['feedback_notes']);
    }

    public function testUnauthenticatedCallsAreRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(401);

        session(['user_id' => null, 'role_slug' => null]);
        $controller = $this->createVerticalsController();
        $controller->save_attendance();
    }

    public function testCustomerRoleIsRejectedFromSavingAttendance(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);

        session(['user_id' => self::$studentId, 'role_slug' => 'customer']);
        $controller = $this->createVerticalsController();
        $controller->save_attendance();
    }
}
