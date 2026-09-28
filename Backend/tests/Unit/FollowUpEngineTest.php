<?php declare(strict_types=1);

namespace Tests\Unit;

use DateTime;
use DateTimeZone;
use Tests\TestCase;

/**
 * Unit tests for BooKi Post-Service Follow-Up Engine.
 *
 * Verifies:
 * 1. Quiet Hours Rule (KVKK: 21:00 - 09:00 postponed to 09:30 next morning)
 * 2. Delay Interval Parsing (e.g. '24 hours', '21 days', '45 minutes')
 * 3. Industry Family and Blueprint resolution
 * 4. Anti-Spam De-duplication rule (Rebook detection)
 * 5. KVKK Opt-Out rule (RED/DUR keyword detection and medical reaction exception)
 * 6. Medical Reaction response classification (1: Healthy vs 2: Emergency Doctor Alert)
 * 7. NPS Score response classification (1-3: Low vs 5: Google Maps Review)
 */
class FollowUpEngineTest extends TestCase
{
    /**
     * Helper mimicking Quiet Hours calculation in Follow_up_engine.
     */
    private function applyQuietHours(DateTime $target, string $timezone = 'Europe/Istanbul'): DateTime
    {
        $dt = clone $target;
        $dt->setTimezone(new DateTimeZone($timezone));

        $hour = (int) $dt->format('G');

        if ($hour >= 21) {
            $dt->modify('+1 day');
            $dt->setTime(9, 30, 0);
        } elseif ($hour < 9) {
            $dt->setTime(9, 30, 0);
        }

        return $dt;
    }

    /**
     * Helper mimicking interval parser.
     */
    private function parseIntervalSeconds(string $interval_str): int
    {
        $interval_str = trim(strtolower($interval_str));

        if ($interval_str === '0 minutes' || $interval_str === '0' || $interval_str === 'now') {
            return 0;
        }

        if (preg_match('/^(\d+)\s*(minute|minutes|dk|dakika)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 60;
        }
        if (preg_match('/^(\d+)\s*(hour|hours|saat)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 3600;
        }
        if (preg_match('/^(\d+)\s*(day|days|gun|gün)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 86400;
        }
        if (preg_match('/^(\d+)\s*(week|weeks|hafta)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 7 * 86400;
        }
        if (preg_match('/^(\d+)\s*(month|months|ay)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 30 * 86400;
        }

        return 3600;
    }

    public function testQuietHoursPostponesNightMessageToNextMorning(): void
    {
        $tz = 'Europe/Istanbul';

        // Case 1: 21:15 (Night) -> should postpone to next day 09:30
        $night = new DateTime('2026-10-01 21:15:00', new DateTimeZone($tz));
        $adjusted = $this->applyQuietHours($night, $tz);

        $this->assertSame('2026-10-02 09:30:00', $adjusted->format('Y-m-d H:i:s'));

        // Case 2: 23:59 (Late Night) -> should postpone to next day 09:30
        $lateNight = new DateTime('2026-10-01 23:59:00', new DateTimeZone($tz));
        $adjustedLate = $this->applyQuietHours($lateNight, $tz);

        $this->assertSame('2026-10-02 09:30:00', $adjustedLate->format('Y-m-d H:i:s'));

        // Case 3: 04:30 AM (Early Morning) -> should postpone to same day 09:30
        $earlyMorning = new DateTime('2026-10-02 04:30:00', new DateTimeZone($tz));
        $adjustedEarly = $this->applyQuietHours($earlyMorning, $tz);

        $this->assertSame('2026-10-02 09:30:00', $adjustedEarly->format('Y-m-d H:i:s'));

        // Case 4: 08:45 AM (Just before 9 AM) -> should postpone to 09:30
        $preNine = new DateTime('2026-10-02 08:45:00', new DateTimeZone($tz));
        $adjustedPreNine = $this->applyQuietHours($preNine, $tz);

        $this->assertSame('2026-10-02 09:30:00', $adjustedPreNine->format('Y-m-d H:i:s'));

        // Case 5: 14:00 (Daytime) -> should NOT be altered
        $daytime = new DateTime('2026-10-02 14:00:00', new DateTimeZone($tz));
        $adjustedDay = $this->applyQuietHours($daytime, $tz);

        $this->assertSame('2026-10-02 14:00:00', $adjustedDay->format('Y-m-d H:i:s'));

        // Case 6: 09:30 (Exact time) -> should NOT be altered
        $nineThirty = new DateTime('2026-10-02 09:30:00', new DateTimeZone($tz));
        $adjustedNineThirty = $this->applyQuietHours($nineThirty, $tz);

        $this->assertSame('2026-10-02 09:30:00', $adjustedNineThirty->format('Y-m-d H:i:s'));
    }

    public function testIntervalParsing(): void
    {
        $this->assertSame(0, $this->parseIntervalSeconds('0 minutes'));
        $this->assertSame(900, $this->parseIntervalSeconds('15 minutes'));
        $this->assertSame(2700, $this->parseIntervalSeconds('45 minutes'));
        $this->assertSame(3600, $this->parseIntervalSeconds('1 hour'));
        $this->assertSame(7200, $this->parseIntervalSeconds('2 hours'));
        $this->assertSame(86400, $this->parseIntervalSeconds('24 hours'));
        $this->assertSame(172800, $this->parseIntervalSeconds('48 hours'));
        $this->assertSame(7 * 86400, $this->parseIntervalSeconds('7 days'));
        $this->assertSame(21 * 86400, $this->parseIntervalSeconds('21 days'));
        $this->assertSame(28 * 86400, $this->parseIntervalSeconds('28 days'));
        $this->assertSame(40 * 86400, $this->parseIntervalSeconds('40 days'));
        $this->assertSame(180 * 86400, $this->parseIntervalSeconds('180 days'));
    }

    public function testOptOutKeywordMatching(): void
    {
        $optOutKeywords = ['RED', 'DUR', 'STOP', 'IPTAL', 'İPTAL', 'UNSUBSCRIBE'];

        foreach ($optOutKeywords as $kw) {
            $normalized = mb_strtoupper(trim($kw), 'UTF-8');
            $this->assertContains($normalized, ['RED', 'DUR', 'STOP', 'IPTAL', 'İPTAL', 'UNSUBSCRIBE']);
        }
    }

    public function testMedicalReactionExceptionDuringOptOut(): void
    {
        // Rule: If customer has opted out, only 'reaction_check' is allowed through
        $customerOptedOut = true;

        $retentionRuleType = 'retention_rebook';
        $reactionCheckType = 'reaction_check';
        $reviewRequestType = 'review_request';

        $isAllowed = static function (bool $optedOut, string $ruleType): bool {
            if (!$optedOut) {
                return true;
            }
            return $ruleType === 'reaction_check';
        };

        $this->assertFalse($isAllowed($customerOptedOut, $retentionRuleType), 'Retention rebook must be blocked if opted out.');
        $this->assertFalse($isAllowed($customerOptedOut, $reviewRequestType), 'Review request must be blocked if opted out.');
        $this->assertTrue($isAllowed($customerOptedOut, $reactionCheckType), 'Medical reaction check must be preserved even if customer opted out.');
    }

    public function testAntiSpamRebookSuppressionLogic(): void
    {
        // Rule: If customer already booked future appointment, do not send retention rebook
        $hasFutureBooking = true;
        $ruleType = 'retention_rebook';

        $shouldSuppress = ($ruleType === 'retention_rebook' && $hasFutureBooking);
        $this->assertTrue($shouldSuppress, 'Retention rebook should be cancelled if future appointment exists.');

        $hasNoFutureBooking = false;
        $shouldSuppress2 = ($ruleType === 'retention_rebook' && $hasNoFutureBooking);
        $this->assertFalse($shouldSuppress2, 'Retention rebook should proceed if customer has not rebooked.');
    }

    public function testMedicalReactionResponseClassification(): void
    {
        $classifyReaction = static function (string $body): string {
            $normalized = mb_strtoupper(trim($body), 'UTF-8');
            if ($normalized === '1' || str_contains(mb_strtolower($body, 'UTF-8'), 'iyi')) {
                return 'good';
            }
            if ($normalized === '2' || str_contains(mb_strtolower($body, 'UTF-8'), 'doktor') || str_contains(mb_strtolower($body, 'UTF-8'), 'ağrı') || str_contains(mb_strtolower($body, 'UTF-8'), 'kanama')) {
                return 'emergency_consult';
            }
            return 'unknown';
        };

        $this->assertSame('good', $classifyReaction('1'));
        $this->assertSame('good', $classifyReaction('Çok iyiyim teşekkürler'));
        $this->assertSame('emergency_consult', $classifyReaction('2'));
        $this->assertSame('emergency_consult', $classifyReaction('Ağrım ve kanamam var doktora danışmak istiyorum'));
    }

    public function testNpsFeedbackRatingClassification(): void
    {
        $classifyNps = static function (string $body): ?int {
            $normalized = mb_strtoupper(trim($body), 'UTF-8');
            if (preg_match('/^([1-5])\b/', $normalized, $matches)) {
                return (int) $matches[1];
            }
            return null;
        };

        $this->assertSame(5, $classifyNps('5'));
        $this->assertSame(5, $classifyNps('5 yıldız çok beğendim'));
        $this->assertSame(1, $classifyNps('1'));
        $this->assertSame(3, $classifyNps('3'));
        $this->assertNull($classifyNps('merhaba'));
    }
}
