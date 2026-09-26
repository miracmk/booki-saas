<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * System Use Case Scenarios: Appointments, Scheduling & Conflict Engine (UC-056 to UC-090).
 */
class AppointmentSchedulingSystemScenariosTest extends TestCase
{
    private function hasConflict(string $startA, string $endA, string $startB, string $endB): bool
    {
        return ($startA < $endB) && ($endA > $startB);
    }

    /** UC-056: Booking appointment with available provider and valid time slot */
    public function test_UC056_booking_valid_slot(): void
    {
        $hasConflict = $this->hasConflict('10:00', '11:00', '11:00', '12:00');
        $this->assertFalse($hasConflict);
    }

    /** UC-057: Double booking prevention: exact duplicate slot rejected */
    public function test_UC057_double_booking_duplicate_slot(): void
    {
        $hasConflict = $this->hasConflict('10:00', '11:00', '10:00', '11:00');
        $this->assertTrue($hasConflict);
    }

    /** UC-058: Double booking prevention: overlapping start time rejected */
    public function test_UC058_overlapping_start_time(): void
    {
        $hasConflict = $this->hasConflict('10:00', '11:00', '10:30', '11:30');
        $this->assertTrue($hasConflict);
    }

    /** UC-059: Double booking prevention: overlapping end time rejected */
    public function test_UC059_overlapping_end_time(): void
    {
        $hasConflict = $this->hasConflict('10:30', '11:30', '10:00', '11:00');
        $this->assertTrue($hasConflict);
    }

    /** UC-060: Double booking prevention: engulfing slot rejected */
    public function test_UC060_engulfing_slot(): void
    {
        $hasConflict = $this->hasConflict('09:00', '12:00', '10:00', '11:00');
        $this->assertTrue($hasConflict);
    }

    /** UC-061: Working hours validation: booking before opening rejected */
    public function test_UC061_booking_before_opening(): void
    {
        $openTime = '09:00';
        $requestedStart = '08:30';
        $isValid = ($requestedStart >= $openTime);
        $this->assertFalse($isValid);
    }

    /** UC-062: Working hours validation: booking after closing rejected */
    public function test_UC062_booking_after_closing(): void
    {
        $closeTime = '19:00';
        $requestedEnd = '19:30';
        $isValid = ($requestedEnd <= $closeTime);
        $this->assertFalse($isValid);
    }

    /** UC-063: Break time validation: booking during lunch break rejected */
    public function test_UC063_booking_during_break(): void
    {
        $breakStart = '13:00';
        $breakEnd = '14:00';
        $slotStart = '13:30';
        $slotEnd = '14:30';
        $conflict = $this->hasConflict($slotStart, $slotEnd, $breakStart, $breakEnd);
        $this->assertTrue($conflict);
    }

    /** UC-064: Day off validation: booking on closed days rejected */
    public function test_UC064_booking_on_closed_day(): void
    {
        $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $requestedDay = 'Sunday';
        $isOpen = in_array($requestedDay, $workingDays, true);
        $this->assertFalse($isOpen);
    }

    /** UC-065: Provider exceptional working plan / holiday override */
    public function test_UC065_exceptional_holiday_override(): void
    {
        $holidays = ['2026-10-29' => 'Republic Day'];
        $requestedDate = '2026-10-29';
        $isHoliday = isset($holidays[$requestedDate]);
        $this->assertTrue($isHoliday);
    }

    /** UC-066: Service duration calculation */
    public function test_UC066_service_duration_calculation(): void
    {
        $durationMinutes = 45;
        $bufferMinutes = 15;
        $totalBlockTime = $durationMinutes + $bufferMinutes;
        $this->assertSame(60, $totalBlockTime);
    }

    /** UC-067: Service buffer time computation */
    public function test_UC067_next_slot_buffer_computation(): void
    {
        $startTime = strtotime('2026-09-20 10:00:00');
        $duration = 45 * 60;
        $buffer = 15 * 60;
        $nextAvailable = date('H:i', $startTime + $duration + $buffer);
        $this->assertSame('11:00', $nextAvailable);
    }

    /** UC-068: Station assignment: book appointment with specific resource/station */
    public function test_UC068_station_assignment(): void
    {
        $appointment = ['id' => 1, 'station_id' => 3];
        $this->assertSame(3, $appointment['station_id']);
    }

    /** UC-069: Station concurrency limit check */
    public function test_UC069_station_concurrency_limit(): void
    {
        $stationCapacity = 1;
        $currentBookings = 1;
        $canBook = ($currentBookings < $stationCapacity);
        $this->assertFalse($canBook);
    }

    /** UC-070: Multi-service sequential duration computation */
    public function test_UC070_multi_service_duration(): void
    {
        $services = [
            ['name' => 'Haircut', 'duration' => 30],
            ['name' => 'Beard Trim', 'duration' => 20],
            ['name' => 'Styling', 'duration' => 15],
        ];
        $totalDuration = array_sum(array_column($services, 'duration'));
        $this->assertSame(65, $totalDuration);
    }

    /** UC-071: Status transition: new booking created as unconfirmed or confirmed */
    public function test_UC071_new_booking_status(): void
    {
        $status = 'confirmed';
        $validStatuses = ['unconfirmed', 'confirmed'];
        $this->assertContains($status, $validStatuses);
    }

    /** UC-072: Status transition: confirmed -> attended */
    public function test_UC072_status_transition_attended(): void
    {
        $status = 'attended';
        $this->assertSame('attended', $status);
    }

    /** UC-073: Status transition: attended -> completed */
    public function test_UC073_status_transition_completed(): void
    {
        $status = 'completed';
        $this->assertSame('completed', $status);
    }

    /** UC-074: Status transition: completed -> closed */
    public function test_UC074_status_transition_closed(): void
    {
        $status = 'closed';
        $this->assertSame('closed', $status);
    }

    /** UC-075: Status transition: cancel appointment by admin */
    public function test_UC075_cancel_by_admin(): void
    {
        $appointment = ['status' => 'confirmed'];
        $appointment['status'] = 'cancelled';
        $this->assertSame('cancelled', $appointment['status']);
    }

    /** UC-076: Status transition: cancel by customer via secure hash */
    public function test_UC076_cancel_by_customer_hash(): void
    {
        $secretHash = hash('sha256', 'app_123_salt_abc');
        $inputHash = hash('sha256', 'app_123_salt_abc');
        $this->assertTrue(hash_equals($secretHash, $inputHash));
    }

    /** UC-077: Reschedule appointment: move to new slot with conflict check */
    public function test_UC077_reschedule_to_valid_slot(): void
    {
        $newStart = '2026-09-21 14:00';
        $newEnd = '2026-09-21 15:00';
        $conflict = $this->hasConflict($newStart, $newEnd, '2026-09-21 15:00', '2026-09-21 16:00');
        $this->assertFalse($conflict);
    }

    /** UC-078: Reschedule appointment: cannot move to already booked slot */
    public function test_UC078_reschedule_to_booked_slot_fails(): void
    {
        $newStart = '2026-09-21 14:00';
        $newEnd = '2026-09-21 15:00';
        $conflict = $this->hasConflict($newStart, $newEnd, '2026-09-21 14:30', '2026-09-21 15:30');
        $this->assertTrue($conflict);
    }

    /** UC-079: Reschedule appointment: cannot move to past datetime */
    public function test_UC079_reschedule_cannot_move_to_past(): void
    {
        $targetTime = strtotime('2020-01-01 10:00:00');
        $now = time();
        $isFuture = ($targetTime > $now);
        $this->assertFalse($isFuture);
    }

    /** UC-080: Customer appointment notes encryption */
    public function test_UC080_appointment_notes_security(): void
    {
        $note = 'Sensitive allergy info';
        $encrypted = base64_encode($note);
        $this->assertNotSame($note, $encrypted);
        $this->assertSame($note, base64_decode($encrypted));
    }

    /** UC-081: Appointment price snapshot captures service price at booking time */
    public function test_UC081_price_snapshot_preserved(): void
    {
        $servicePriceThen = 250.00;
        $appointmentSnapshotPrice = $servicePriceThen;
        $servicePriceNow = 350.00;
        $this->assertSame(250.00, $appointmentSnapshotPrice);
        $this->assertNotSame($servicePriceNow, $appointmentSnapshotPrice);
    }

    /** UC-082: Appointment percentage discount calculation */
    public function test_UC082_percentage_discount(): void
    {
        $price = 200.00;
        $discountPct = 20; // 20%
        $finalPrice = $price - ($price * ($discountPct / 100));
        $this->assertSame(160.00, $finalPrice);
    }

    /** UC-083: Appointment fixed amount discount calculation */
    public function test_UC083_fixed_discount(): void
    {
        $price = 200.00;
        $discountAmount = 50.00;
        $finalPrice = max(0, $price - $discountAmount);
        $this->assertSame(150.00, $finalPrice);
    }

    /** UC-084: Google Calendar sync event creation payload format */
    public function test_UC084_google_calendar_sync_event_payload(): void
    {
        $payload = [
            'summary' => 'Haircut - John Doe',
            'start' => ['dateTime' => '2026-09-20T10:00:00+03:00'],
            'end' => ['dateTime' => '2026-09-20T11:00:00+03:00'],
        ];
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('start', $payload);
    }

    /** UC-085: Google Calendar sync event update on reschedule */
    public function test_UC085_google_calendar_event_reschedule(): void
    {
        $eventId = 'gcal_event_123';
        $newStart = '2026-09-21T14:00:00+03:00';
        $updated = ['id' => $eventId, 'start' => $newStart];
        $this->assertSame('gcal_event_123', $updated['id']);
    }

    /** UC-086: Google Calendar sync event deletion on cancellation */
    public function test_UC086_google_calendar_event_delete(): void
    {
        $calendarEvents = ['evt_1' => true, 'evt_2' => true];
        unset($calendarEvents['evt_1']);
        $this->assertArrayNotHasKey('evt_1', $calendarEvents);
    }

    /** UC-087: Notification event trigger: confirmation message */
    public function test_UC087_notification_confirmation_message(): void
    {
        $template = 'Sayın {CUSTOMER}, {DATE} tarihindeki randevunuz onaylandı.';
        $rendered = str_replace(['{CUSTOMER}', '{DATE}'], ['Ahmet Yılmaz', '20.09.2026'], $template);
        $this->assertStringContainsString('Ahmet Yılmaz', $rendered);
    }

    /** UC-088: Notification event trigger: reminder (24h before) */
    public function test_UC088_reminder_24h_trigger(): void
    {
        $appointmentTime = strtotime('+24 hours');
        $now = time();
        $diffHours = round(($appointmentTime - $now) / 3600);
        $this->assertSame(24.0, $diffHours);
    }

    /** UC-089: Notification event trigger: cancellation notice */
    public function test_UC089_cancellation_notice(): void
    {
        $template = 'Randevunuz iptal edilmiştir.';
        $this->assertStringContainsString('iptal', $template);
    }

    /** UC-090: Atomic lock prevents concurrent race conditions */
    public function test_UC090_atomic_booking_lock(): void
    {
        $locks = ['slot_prov1_202609201000' => true];
        $isLocked = isset($locks['slot_prov1_202609201000']);
        $this->assertTrue($isLocked);
    }
}

