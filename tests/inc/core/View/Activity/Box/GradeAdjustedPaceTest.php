<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Box;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Route\Entity as Route;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\View\Activity\Context;

final class GradeAdjustedPaceTest extends TestCase
{
    #[DataProvider('units')]
    public function testDisplaysAdjustedPaceInTheSportUnit(int $unit, string $expectedValue, string $expectedUnit, string $expectedLabel): void
    {
        $context = $this->createStub(Context::class);
        $context->method('sport')->willReturn(new Sport([
            Sport::INTERNAL_SPORT_ID => SportProfile::RUNNING,
            Sport::PACE_UNIT => $unit,
        ]));
        $context->method('activity')->willReturn(new Activity([
            Activity::DISTANCE => 1.0,
            Activity::TIME_IN_SECONDS => 360,
        ]));
        $context->method('trackdata')->willReturn(new Trackdata([
            Trackdata::TIME => [0, 100, 200, 300],
            Trackdata::DISTANCE => [0, 0.3, 0.6, 1],
        ]));
        $context->method('route')->willReturn(new Route([
            Route::ELEVATIONS_ORIGINAL => [125, 125, 125, 125],
        ]));
        $context->method('hasRoute')->willReturn(true);
        $context->method('hasTrackdata')->willReturn(true);

        $this->assertTrue(GradeAdjustedPace::hasPace($context));
        $data = new GradeAdjustedPace($context)->templateData();

        $this->assertSame($expectedValue, $data['value']);
        $this->assertSame($expectedUnit, $data['unit']);
        $this->assertSame(__('avg.') . ' ' . $expectedLabel, $data['info']);
    }

    public static function units(): iterable
    {
        yield 'pace' => [PaceEnum::SECONDS_PER_KILOMETER, '5:00', '/km', 'GAP'];
        yield 'speed' => [PaceEnum::KILOMETER_PER_HOUR, '12,0', '&nbsp;km/h', 'GAP Speed'];
    }
}
