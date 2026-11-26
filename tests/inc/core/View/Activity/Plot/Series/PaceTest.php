<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Plot\Series;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plot;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;

final class PaceTest extends TestCase
{
    /**
     * @param list<float> $values
     * @param list<int>|string $ticksOrUnit
     */
    #[DataProvider('paceUnits')]
    public function testAddsPlotDataAndAxisForSportUnit(?int $unit, array $values, array|string $ticksOrUnit): void
    {
        $sport = new Sport($unit === null ? [] : [
            Sport::PACE_UNIT => (string) $unit,
        ]);
        $trackdata = new Trackdata([
            Trackdata::TIME => [0, 10, 20],
        ]);
        $trackdata->setTheoreticalPace([0, 300, 600]);

        $context = $this->createStub(Context::class);
        $context->method('sport')->willReturn($sport);
        $context->method('trackdata')->willReturn($trackdata);

        $plot = new Plot('pace-test');
        new Pace($context)->addTo($plot, 1);

        $this->assertSame([
            10000 => $values[0],
            20000 => $values[1],
        ], $plot->Data[0]['data']);
        $axis = $plot->Options['yaxes'][0];
        if (is_array($ticksOrUnit)) {
            $this->assertSame($ticksOrUnit, $axis['ticks']);
            $this->assertSame('%M:%S', $axis['timeformat']);
        } else {
            $this->assertStringContainsString(" + ' " . $ticksOrUnit . "';", (string) $axis['tickFormatter']);
        }
    }

    public static function paceUnits(): iterable
    {
        yield 'min/km' => [PaceEnum::SECONDS_PER_KILOMETER, [300000.0, 600000.0], [240000, 300000, 360000, 480000]];
        yield 'min/mi' => [PaceEnum::SECONDS_PER_MILE, [483000.0, 966000.0], [360000, 450000, 600000, 900000]];
        yield 'min/100m' => [PaceEnum::SECONDS_PER_100M, [30000.0, 60000.0], [10000, 60000]];
        yield 'min/100y' => [PaceEnum::SECONDS_PER_100Y, [27000.0, 55000.0], [10000, 60000]];
        yield 'min/500m' => [PaceEnum::SECONDS_PER_500M, [150000.0, 300000.0], [60000, 120000, 180000, 240000]];
        yield 'min/500y' => [PaceEnum::SECONDS_PER_500Y, [137000.0, 274000.0], [60000, 120000, 180000, 240000]];
        yield 'km/h' => [PaceEnum::KILOMETER_PER_HOUR, [12.0, 6.0], 'km/h'];
        yield 'mph' => [PaceEnum::MILES_PER_HOUR, [7.5, 3.7], 'mph'];
        yield 'm/s' => [PaceEnum::METER_PER_SECOND, [3.3, 1.7], 'm/s'];
        yield 'missing ID' => [null, [12.0, 6.0], 'km/h'];
        yield 'unknown ID' => [999, [12.0, 6.0], 'km/h'];
    }
}
