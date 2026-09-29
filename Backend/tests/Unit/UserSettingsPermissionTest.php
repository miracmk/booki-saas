<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TenantTestCase;

/**
 * Unit tests for user_settings permission across staff roles.
 */
class UserSettingsPermissionTest extends TenantTestCase
{
    private $CI;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CI = self::$ci;
        $this->CI->load->library('permission_service');
    }

    /**
     * Test all staff roles can view and edit user_settings.
     */
    public function testStaffRolesCanAccessUserSettings(): void
    {
        $staff_roles = [
            'admin',
            'owner',
            'manager',
            'general_manager',
            'secretary',
            'provider',
            'cashier',
            'waiter',
            'doctor',
            'nurse',
            'reception',
            'professional',
            'therapist',
            'inventory',
        ];

        foreach ($staff_roles as $role_slug) {
            $_SESSION['user_id'] = 999;
            $_SESSION['role_slug'] = $role_slug;
            $_SESSION['is_admin'] = in_array($role_slug, ['admin', 'owner', 'manager', 'general_manager'], true) ? 1 : 0;

            $can_view = $this->CI->permission_service->can('view', 'user_settings', 999);
            $can_edit = $this->CI->permission_service->can('edit', 'user_settings', 999);

            $this->assertTrue($can_view, "Staff role '{$role_slug}' should be allowed to view user_settings");
            $this->assertTrue($can_edit, "Staff role '{$role_slug}' should be allowed to edit user_settings");
        }
    }

    /**
     * Test customer can view user_settings and customers (read-only), but cannot edit or delete.
     */
    public function testCustomerHasReadOnlyAccessToUserSettingsAndCustomerCard(): void
    {
        $_SESSION['user_id'] = 999;
        $_SESSION['role_slug'] = 'customer';
        $_SESSION['is_admin'] = 0;

        $can_view_settings = $this->CI->permission_service->can('view', 'user_settings', 999);
        $can_edit_settings = $this->CI->permission_service->can('edit', 'user_settings', 999);
        $can_delete_settings = $this->CI->permission_service->can('delete', 'user_settings', 999);

        $can_view_customers = $this->CI->permission_service->can('view', 'customers', 999);
        $can_edit_customers = $this->CI->permission_service->can('edit', 'customers', 999);
        $can_delete_customers = $this->CI->permission_service->can('delete', 'customers', 999);

        $this->assertTrue($can_view_settings, "Customer role should be allowed to VIEW user_settings");
        $this->assertFalse($can_edit_settings, "Customer role must NOT be allowed to EDIT user_settings");
        $this->assertFalse($can_delete_settings, "Customer role must NOT be allowed to DELETE user_settings");

        $this->assertTrue($can_view_customers, "Customer role should be allowed to VIEW customers data");
        $this->assertFalse($can_edit_customers, "Customer role must NOT be allowed to EDIT customers data");
        $this->assertFalse($can_delete_customers, "Customer role must NOT be allowed to DELETE customers data");
    }
}
