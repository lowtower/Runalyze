<?php

declare(strict_types=1);

namespace Runalyze\Tests\Sports\Running;

use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Sports\Running\MarathonShape;

final class MarathonShapeTest extends \PHPUnit\Framework\TestCase
{
    protected function getDefaultConfiguration(): \Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance
    {
        return new BasicEndurance();
    }

    public function testThatVerySmallVO2maxValuesAreIgnored(): void
    {
        $shape = new MarathonShape(1.0, $this->getDefaultConfiguration());

        $this->assertEqualsWithDelta(22.0, $shape->getTargetForLongJogEachWeek(), 0.1);
        $this->assertEqualsWithDelta(38.0, $shape->getTargetForWeeklyMileage(), 1.0);

        $shape->setEffectiveVO2max(20.0);

        $this->assertEqualsWithDelta(22.0, $shape->getTargetForLongJogEachWeek(), 0.1);
        $this->assertEqualsWithDelta(38.0, $shape->getTargetForWeeklyMileage(), 1.0);
    }

    public function testSimpleExample(): void
    {
        $config = $this->getDefaultConfiguration();
        $config->set('BE_DAYS_FOR_LONGJOGS', '7');
        $config->set('BE_DAYS_FOR_WEEK_KM', '7');
        $config->set('BE_PERCENTAGE_WEEK_KM', '0.50');

        $shape = new MarathonShape(60.0, $config);

        $this->assertEqualsWithDelta(32.5, $shape->getTargetForLongJogEachWeek(), 1.0);
        $this->assertEqualsWithDelta(104.0, $shape->getTargetForWeeklyMileage(), 1.0);
        $this->assertEqualsWithDelta(75.0, $shape->getShapeFor(52.0, 1.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(75.0, $shape->getShapeFor(104.0, 0.5), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(100.0, $shape->getShapeFor(104.0, 1.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(125.0, $shape->getShapeFor(52.0, 2.0), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(100.0, $shape->getShapeFor(0.0, 2.0), PHP_FLOAT_EPSILON);
    }
}
