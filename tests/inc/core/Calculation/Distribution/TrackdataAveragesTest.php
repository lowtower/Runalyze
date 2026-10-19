<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Distribution;

use Runalyze\Model\Trackdata;

final class TrackdataAveragesTest extends \PHPUnit\Framework\TestCase
{
    public function testNoTimeData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrackdataAverages(new Trackdata\Entity([]), []);
    }

    public function testEmptyTimeData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrackdataAverages(
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [0, 0, 0, 0, 0],
            ]),
            []
        );
    }

    public function testNoRequestedKeys(): void
    {
        $Averages = new TrackdataAverages(
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [1, 2, 3, 4, 5],
            ]),
            []
        );

        $this->assertEquals([], $Averages->averages());
    }

    public function testInvalidAverageKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $Averages = new TrackdataAverages(
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [1, 2, 3, 4, 5],
            ]),
            [
                Trackdata\Entity::HEARTRATE,
            ]
        );

        $Averages->average(Trackdata\Entity::CADENCE);
    }

    public function testSimpleAverages(): void
    {
        $Averages = new TrackdataAverages(
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [1, 3, 5, 10],
                Trackdata\Entity::HEARTRATE => [120, 130, 140, 150],
            ]),
            [
                Trackdata\Entity::HEARTRATE,
                Trackdata\Entity::CADENCE,
            ]
        );

        $this->assertEquals(141, $Averages->average(Trackdata\Entity::HEARTRATE));
        $this->assertEquals(null, $Averages->average(Trackdata\Entity::CADENCE));
    }

    public function testAveragesWithZero(): void
    {
        $Averages = new TrackdataAverages(
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [1, 3, 5, 10],
                Trackdata\Entity::HEARTRATE => [0, 0, 0, 0],
                Trackdata\Entity::CADENCE => [120, 120, 0, 0],
                Trackdata\Entity::STRIDE_LENGTH => [1, 1, 0, 0],
            ]),
            [
                Trackdata\Entity::HEARTRATE,
                Trackdata\Entity::CADENCE,
                Trackdata\Entity::STRIDE_LENGTH,
            ]
        );

        $this->assertEquals(0, $Averages->average(Trackdata\Entity::HEARTRATE));
        $this->assertEquals(120, $Averages->average(Trackdata\Entity::CADENCE));
        $this->assertEquals(1, $Averages->average(Trackdata\Entity::STRIDE_LENGTH));
    }
}
