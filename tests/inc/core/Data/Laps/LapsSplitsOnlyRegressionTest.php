<?php

namespace Runalyze\Data\Laps;

use Runalyze\Model\Activity\Splits;

/**
 * Regression test for Lap objects created from splits-only data
 * (i.e. without any Trackdata\Entity)
 */
class LapsSplitsOnlyRegressionTest extends \PHPUnit\Framework\TestCase
{
    protected Laps $Laps;

    protected function setUp(): void
    {
        $this->Laps = new Laps();
    }

    private function createSingleSplit(): Splits\Entity
    {
        $Splits = new Splits\Entity();
        $Splits->add(new Splits\Split(1.0, 300, false));

        return $Splits;
    }

    public function testLapFromSplitsOnlyReportsNoTrackValuesInsteadOfCrashing(): void
    {
        $this->Laps->readFrom($this->createSingleSplit());

        $this->assertSame(1, $this->Laps->num());

        $Lap = $this->Laps->at(0);

        $this->assertFalse($Lap->hasTrackValues());
        $this->assertFalse($Lap->hasHR());
    }

    public function testTrackDurationOnSplitsOnlyLapThrowsRuntimeException(): void
    {
        $this->Laps->readFrom($this->createSingleSplit());

        $this->expectException(\RuntimeException::class);

        $this->Laps->at(0)
            ->trackDuration();
    }

    public function testTrackDistanceOnSplitsOnlyLapThrowsRuntimeException(): void
    {
        $this->Laps->readFrom($this->createSingleSplit());

        $this->expectException(\RuntimeException::class);

        $this->Laps->at(0)
            ->trackDistance();
    }

    public function testHRavgOnSplitsOnlyLapThrowsRuntimeException(): void
    {
        $this->Laps->readFrom($this->createSingleSplit());

        $this->expectException(\RuntimeException::class);

        $this->Laps->at(0)
            ->HRavg();
    }

    public function testHRmaxOnSplitsOnlyLapThrowsRuntimeException(): void
    {
        $this->Laps->readFrom($this->createSingleSplit());

        $this->expectException(\RuntimeException::class);

        $this->Laps->at(0)
            ->HRmax();
    }

    public function testLapWithExplicitlySetTrackValuesAndHrReportsTrue(): void
    {
        $Lap = new Lap(300.0, 1.0);
        $Lap->setTrackDuration(305.0);
        $Lap->setTrackDistance(1.02);
        $Lap->setHR(140.0, 165.0);

        $this->assertTrue($Lap->hasTrackValues());
        $this->assertTrue($Lap->hasHR());

        $this->assertSame(305.0, $Lap->trackDuration()->seconds());
        $this->assertSame(1.02, $Lap->trackDistance()->kilometer());
        $this->assertEquals(140.0, $Lap->HRavg()->inBPM());
        $this->assertEquals(165.0, $Lap->HRmax()->inBPM());
    }
}
