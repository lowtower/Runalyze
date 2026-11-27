<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;

final class TrainingPaceTablesTest extends TestCase
{
    #[DataProvider('paceFormats')]
    public function testTablesUseTheSportUnit(int $unit, string $lap, string $zone, string $emptyAverage): void
    {
        $trackdata = new Trackdata([
            Trackdata::TIME => [0, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300],
            Trackdata::DISTANCE => [0, 0.2, 0.4, 0.65, 0.82, 1, 1.21, 1.32, 1.5, 1.75, 2],
            Trackdata::HEARTRATE => [125, 140, 140, 155, 155, 155, 160, 160, 170, 175, 175],
        ]);
        $trackdata->calculatePaceArray();

        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn(new Activity([
            Activity::MAX_HR_USER => 190,
        ]));
        $context->method('sport')->willReturn(new Sport([
            Sport::PACE_UNIT => $unit,
        ]));
        $context->method('trackdata')->willReturn($trackdata);
        $context->method('hasRoute')->willReturn(false);

        $this->assertSame($lap, new TableLapsComputed($context)->templateData()['bodies'][0]['rows'][0]['cells'][2]['content']);
        $this->assertSame($zone, new TableZonesPace($context)->templateData()['rows'][1]['zone']);
        $heartRateRows = new TableZonesHeartrate($context)->templateData()['rows'];
        $this->assertSame($emptyAverage, $heartRateRows[0]['average']);
        $this->assertSame(substr($zone, strlen('&gt; ')), $heartRateRows[1]['average']);
    }

    public static function paceFormats(): iterable
    {
        yield 'pace' => [PaceEnum::SECONDS_PER_KILOMETER, '2:30<small>/km</small>', '&gt; 2:30/km', '-'];
        yield 'speed' => [PaceEnum::KILOMETER_PER_HOUR, '24,0<small>&nbsp;km/h</small>', '&gt; 24,0&nbsp;km/h', '0,0&nbsp;km/h'];
    }
}
