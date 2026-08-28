<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for pure unit tests.
 *
 * This class is used for tests that do not require database access or framework initialization.
 * For integration tests that need a live tenant database connection, use TenantTestCase instead.
 */
abstract class TestCase extends BaseTestCase
{
}
