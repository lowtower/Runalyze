<?php

declare(strict_types=1);

namespace Runalyze\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base class for all tests that depend on the database.
 * Isolates each test in a transaction that is rolled back on tearDown.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected \PDOforRunalyze $DB;

    protected function setUp(): void
    {
        $this->DB = \DB::getInstance();
        $this->DB->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->DB->inTransaction()) {
                $this->DB->rollBack();
            }
        } catch (\Exception $exception) {
            // Ignore errors during rollback
            throw new \RuntimeException(
                'catched Exception: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }
}
