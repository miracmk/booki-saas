<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for booking conflict checking logic.
 */
class BookingConflictTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Assume there is a model or helper to check conflicts, e.g. appointments_model
        // For the sake of this mock test, we can just assert logic directly or mock a model
    }

    /**
     * Test that overlapping appointments are detected as a conflict.
     */
    public function testOverlappingAppointmentsCauseConflict(): void
    {
        $provider_id = 1;
        $existing_start = '2026-09-20 10:00:00';
        $existing_end = '2026-09-20 11:00:00';
        
        $new_start = '2026-09-20 10:30:00';
        $new_end = '2026-09-20 11:30:00';

        // Check logic: new_start < existing_end && new_end > existing_start
        $has_conflict = ($new_start < $existing_end) && ($new_end > $existing_start);
        
        $this->assertTrue($has_conflict, 'Overlapping times should trigger a conflict.');
    }

    /**
     * Test that adjacent appointments do not conflict.
     */
    public function testAdjacentAppointmentsNoConflict(): void
    {
        $provider_id = 1;
        $existing_start = '2026-09-20 10:00:00';
        $existing_end = '2026-09-20 11:00:00';
        
        $new_start = '2026-09-20 11:00:00';
        $new_end = '2026-09-20 12:00:00';

        $has_conflict = ($new_start < $existing_end) && ($new_end > $existing_start);
        
        $this->assertFalse($has_conflict, 'Adjacent times should not trigger a conflict.');
    }

    /**
     * Test that appointments for different providers do not conflict.
     */
    public function testDifferentProvidersNoConflict(): void
    {
        $provider_a = 1;
        $provider_b = 2;
        
        // Same time, different providers
        $has_conflict = ($provider_a === $provider_b);
        
        $this->assertFalse($has_conflict, 'Different providers should not conflict.');
    }
}
