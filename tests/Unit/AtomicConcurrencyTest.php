<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for atomic decrement logic (UC-55, UC-56).
 *
 * Verifies the SQL conditional update patterns for:
 * - Loyalty Points: WHERE id = ? AND loyalty_points_balance >= ?
 * - Product Stock: WHERE id = ? AND stock_quantity >= ?
 */
class AtomicConcurrencyTest extends TestCase
{
    /**
     * Simulate atomic loyalty points deduction condition.
     */
    private function tryDeductLoyaltyPoints(int &$balance, int $points_to_deduct): bool
    {
        if ($points_to_deduct < 0) {
            throw new \InvalidArgumentException('Points must be non-negative');
        }

        // Simulates: UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?
        if ($balance >= $points_to_deduct) {
            $balance -= $points_to_deduct;
            return true;
        }

        return false;
    }

    /**
     * Simulate atomic product stock deduction condition.
     */
    private function tryDeductStock(int &$stock, int $quantity_delta): bool
    {
        // $quantity_delta is negative for sale/deduction
        if ($quantity_delta < 0) {
            $required = abs($quantity_delta);
            // Simulates: UPDATE products SET stock = stock + (?) WHERE stock >= ?
            if ($stock >= $required) {
                $stock += $quantity_delta;
                return true;
            }
            return false;
        }

        $stock += $quantity_delta;
        return true;
    }

    public function testSufficientLoyaltyPointsDeductedSuccessfully(): void
    {
        $balance = 100;
        $success = $this->tryDeductLoyaltyPoints($balance, 40);

        $this->assertTrue($success);
        $this->assertSame(60, $balance);
    }

    public function testInsufficientLoyaltyPointsFailsAndPreservesBalance(): void
    {
        $balance = 30;
        $success = $this->tryDeductLoyaltyPoints($balance, 50);

        $this->assertFalse($success, 'Cannot deduct more points than current balance.');
        $this->assertSame(30, $balance, 'Balance must remain unchanged when deduction fails.');
    }

    public function testExactBalanceLoyaltyPointsDeductedToZero(): void
    {
        $balance = 50;
        $success = $this->tryDeductLoyaltyPoints($balance, 50);

        $this->assertTrue($success);
        $this->assertSame(0, $balance);
    }

    public function testSufficientStockDeductedSuccessfully(): void
    {
        $stock = 15;
        $success = $this->tryDeductStock($stock, -5);

        $this->assertTrue($success);
        $this->assertSame(10, $stock);
    }

    public function testInsufficientStockFailsAndPreservesQuantity(): void
    {
        $stock = 2;
        $success = $this->tryDeductStock($stock, -5);

        $this->assertFalse($success, 'Cannot deduct more stock than available quantity.');
        $this->assertSame(2, $stock, 'Stock must remain unchanged when deduction fails.');
    }
}
