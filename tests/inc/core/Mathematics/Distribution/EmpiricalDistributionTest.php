<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Distribution;

use Runalyze\Mathematics\Distribution\EmpiricalDistribution;

class EmpiricalDistributionTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleArray(): void
    {
        $dist = new EmpiricalDistribution([10, 15, 15, 20]);

        $this->assertEquals([
            10 => 1,
            15 => 2,
            20 => 1,
        ], $dist->histogram());
    }

    public function testDataIsAlreadyTheHistogramFlagUsesDataDirectly(): void
    {
        $dist = new EmpiricalDistribution([
            10 => 5,
            20 => 3,
        ], true);

        $this->assertSame([
            10 => 5,
            20 => 3,
        ], $dist->histogram());
    }

    public function testEmptyArrayResultsInEmptyHistogram(): void
    {
        $dist = new EmpiricalDistribution([]);

        $this->assertSame([], $dist->histogram());
    }
}
