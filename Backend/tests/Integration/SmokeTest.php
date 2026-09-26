<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * Throwaway smoke test to verify TenantTestCase's CI3 boot mechanism actually works.
 * Not a real feature test - just proves the infrastructure is sound.
 */
class SmokeTest extends TenantTestCase
{
    public function testCiBoots(): void
    {
        $ci = self::ci();
        $this->assertIsObject($ci);
        $this->assertIsObject($ci->db);
    }

    public function testDbConnectionWorks(): void
    {
        $result = self::db()->query('SELECT 1 as one')->row_array();
        $this->assertSame('1', (string) $result['one']);
    }
}
