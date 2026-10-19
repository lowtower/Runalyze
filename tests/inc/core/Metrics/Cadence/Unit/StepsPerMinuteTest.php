<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Cadence\Unit;

use Runalyze\Metrics\Cadence\Unit\StepsPerMinute;

final class StepsPerMinuteTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new StepsPerMinute();

        $this->assertEquals(180, $unit->fromBaseUnit(90));
        $this->assertEquals(85, $unit->toBaseUnit(170));
    }
}
