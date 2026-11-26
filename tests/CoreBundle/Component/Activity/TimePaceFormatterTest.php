<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\TimePaceFormatter;
use Runalyze\Metrics\Velocity\Unit\SecondsPer100m;
use Runalyze\Metrics\Velocity\Unit\SecondsPerKilometer;

final class TimePaceFormatterTest extends TestCase
{
    public function testFormatsRunningPaceAndMissingDistance(): void
    {
        $formatter = new TimePaceFormatter(new SecondsPerKilometer());

        $this->assertSame('4:51/km', $formatter->format(TimePaceFormatter::secondsPerKilometer(291.5, 1.003)));
        $this->assertSame('-:--/km', $formatter->format(TimePaceFormatter::secondsPerKilometer(50.0, 0.0)));
        $this->assertSame('1:01:00/km', $formatter->format(3660));
    }

    public function testFormatsSwimmingIntervalAndItsComparison(): void
    {
        $formatter = new TimePaceFormatter(new SecondsPer100m());

        $this->assertSame('1:00/100m', $formatter->format(TimePaceFormatter::secondsPerKilometer(30.0, 0.05)));
        $this->assertSame('<span class="plus">+0:01/100m</span>', $formatter->compare(590, 600));
        $this->assertSame('<span class="minus">-0:01/100m</span>', $formatter->compare(610, 600));
        $this->assertSame('<span class="plus">+0:00/100m</span>', $formatter->compare(600, 600));
        $this->assertSame('', $formatter->compare(0, 600));
    }
}
