<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\AbstractMetric;

class AbstractMetricTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected(): void
    {
        $mock = new class() extends AbstractMetric {
            public function getBaseUnitClass(): string
            {
                return self::class;
            }
        };

        $this->assertFalse($mock->isKnown());
        $this->assertEquals(42, $mock->setValue(42)->getValue());
        $this->assertTrue($mock->isKnown());
        $this->assertNull($mock->setValue(null)->getValue());
        $this->assertFalse($mock->isKnown());
    }
}
