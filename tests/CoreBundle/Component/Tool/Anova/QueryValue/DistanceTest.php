<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Distance;
use Runalyze\Metrics\Distance\Unit\Kilometer;

class DistanceTest extends TestCase
{
    public function testGetColumnReturnsDistance(): void
    {
        $distance = new Distance();

        $reflection = new \ReflectionObject($distance);
        $method = $reflection->getMethod('getColumn');

        $this->assertSame('distance', $method->invoke($distance));
    }

    public function testGetValueUnitDelegatesToUnitSystemsDistanceUnit(): void
    {
        $expectedUnit = new Kilometer();

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getDistanceUnit')
            ->willReturn($expectedUnit);

        $distance = new Distance();
        $result = $distance->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }
}
