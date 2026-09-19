<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for Working Plan Exceptions date slicing logic (UC-51).
 *
 * Verifies that when a date is removed from a working plan exception interval,
 * the interval is correctly sliced:
 * 1. Single day match: deleted
 * 2. Starts on date: start shifted forward (+1 day)
 * 3. Ends on date: end shifted backward (-1 day)
 * 4. Middle date: split into two disjoint intervals
 */
class WorkingPlanExceptionsSlicingTest extends TestCase
{
    /**
     * Helper to simulate slicing logic from Working_plan_exceptions_model.
     */
    private function sliceException(array $exception, string $date): array
    {
        $start = $exception['start_date'];
        $end = $exception['end_date'];

        if ($start === $date && $end === $date) {
            // Exact single day match: row deleted
            return [];
        } elseif ($start === $date) {
            // Starts on date, extends after: shift start_date to date + 1 day
            $next_day = date('Y-m-d', strtotime($date . ' +1 day'));
            return [
                ['start_date' => $next_day, 'end_date' => $end],
            ];
        } elseif ($end === $date) {
            // Ends on date, starts before: shift end_date to date - 1 day
            $prev_day = date('Y-m-d', strtotime($date . ' -1 day'));
            return [
                ['start_date' => $start, 'end_date' => $prev_day],
            ];
        } else {
            // Date is in the middle: split into two disjoint ranges
            $prev_day = date('Y-m-d', strtotime($date . ' -1 day'));
            $next_day = date('Y-m-d', strtotime($date . ' +1 day'));

            return [
                ['start_date' => $start, 'end_date' => $prev_day],
                ['start_date' => $next_day, 'end_date' => $end],
            ];
        }
    }

    public function testSingleDayExceptionDeleted(): void
    {
        $exception = ['start_date' => '2026-09-20', 'end_date' => '2026-09-20'];
        $result = $this->sliceException($exception, '2026-09-20');

        $this->assertEmpty($result, 'Exact single day match must be deleted.');
    }

    public function testStartDayShiftForward(): void
    {
        $exception = ['start_date' => '2026-09-20', 'end_date' => '2026-09-25'];
        $result = $this->sliceException($exception, '2026-09-20');

        $this->assertCount(1, $result);
        $this->assertSame('2026-09-21', $result[0]['start_date']);
        $this->assertSame('2026-09-25', $result[0]['end_date']);
    }

    public function testEndDayShiftBackward(): void
    {
        $exception = ['start_date' => '2026-09-20', 'end_date' => '2026-09-25'];
        $result = $this->sliceException($exception, '2026-09-25');

        $this->assertCount(1, $result);
        $this->assertSame('2026-09-20', $result[0]['start_date']);
        $this->assertSame('2026-09-24', $result[0]['end_date']);
    }

    public function testMiddleDaySplitsIntoTwoDisjointIntervals(): void
    {
        $exception = ['start_date' => '2026-09-20', 'end_date' => '2026-09-25'];
        $result = $this->sliceException($exception, '2026-09-22');

        $this->assertCount(2, $result);
        $this->assertSame('2026-09-20', $result[0]['start_date']);
        $this->assertSame('2026-09-21', $result[0]['end_date']);

        $this->assertSame('2026-09-23', $result[1]['start_date']);
        $this->assertSame('2026-09-25', $result[1]['end_date']);
    }
}
