<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\PaceCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;

final class PaceCalculatorTest extends TestCase
{
    private PaceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PaceCalculator();
    }

    private function createTrackdata(bool $hasTime, bool $hasDistance): Trackdata&MockObject
    {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method('hasTime')
            ->willReturn($hasTime);
        $trackData->method('hasDistance')
            ->willReturn($hasDistance);

        return $trackData;
    }

    public function testCalculateForSetsPaceToNullWhenTrackdataHasNoTime(): void
    {
        $trackData = $this->createTrackdata(false, true);

        $trackData->expects(self::once())->method('setPace')->with(null);

        $trackData->expects(self::never())->method('getTime');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData);
    }

    public function testCalculateForSetsPaceToNullWhenTrackdataHasNoDistance(): void
    {
        $trackData = $this->createTrackdata(true, false);

        $trackData->expects(self::once())->method('setPace')->with(null);

        $trackData->expects(self::never())->method('getTime');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData);
    }

    public function testCalculateForSetsPaceToNullWhenBothConditionsFail(): void
    {
        $trackData = $this->createTrackdata(false, false);

        $trackData->expects(self::once())->method('setPace')->with(null);

        $this->calculator->calculateFor($trackData);
    }
}
