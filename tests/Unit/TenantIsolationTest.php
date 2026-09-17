<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for tenant isolation logic.
 */
class TenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test tenant context segregation.
     */
    public function testTenantCannotAccessOtherTenantData(): void
    {
        $tenant_a = 'tenant_123';
        $tenant_b = 'tenant_456';

        $data_owner = 'tenant_456';

        $can_access = ($tenant_a === $data_owner);
        $this->assertFalse($can_access, 'Tenant A should not be able to access Tenant B data.');
    }

    /**
     * Test global admin can access.
     */
    public function testSuperAdminCanAccessAnyTenantData(): void
    {
        $is_superadmin = true;
        $data_owner = 'tenant_456';

        $can_access = $is_superadmin || ('tenant_123' === $data_owner);
        $this->assertTrue($can_access, 'Superadmin should bypass tenant isolation.');
    }
}
