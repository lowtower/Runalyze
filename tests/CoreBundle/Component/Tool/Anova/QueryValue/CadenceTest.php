<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Cadence;
use Runalyze\Metrics\Cadence\Unit\RoundsPerMinute;

class CadenceTest extends TestCase
{
    public function testGetColumnReturnsCadence(): void
    {
        $cadence = new Cadence();

        $reflection = new \ReflectionObject($cadence);
        $method = $reflection->getMethod('getColumn');

        $this->assertSame('cadence', $method->invoke($cadence));
    }

    public function testGetValueUnitDelegatesToUnitSystemsCadenceUnit(): void
    {
        $expectedUnit = new RoundsPerMinute();

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getCadenceUnit')
            ->willReturn($expectedUnit);

        $cadence = new Cadence();
        $result = $cadence->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }
}
