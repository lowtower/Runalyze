<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration\Category;

use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;

class BasicEnduranceTest extends \PHPUnit\Framework\TestCase
{
    public function testDaysToConsiderForWeeklyMileageAdaptedForFirstActivityDate(): void
    {
        $config = new BasicEndurance();

        $this->assertSame(182, $config->getDaysToConsiderForWeeklyMileage());
        $this->assertSame(182, $config->getDaysToConsiderForWeeklyMileage(365));
        $this->assertSame(150, $config->getDaysToConsiderForWeeklyMileage(150));
        $this->assertSame(92, $config->getDaysToConsiderForWeeklyMileage(92));
        $this->assertSame(70, $config->getDaysToConsiderForWeeklyMileage(42));
        $this->assertSame(70, $config->getDaysToConsiderForWeeklyMileage(0));
    }
}
