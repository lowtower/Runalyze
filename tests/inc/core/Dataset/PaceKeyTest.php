<?php

declare(strict_types=1);

namespace Runalyze\Dataset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Dataset\Keys\Pace;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Profile\Sport\SportProfile;

final class PaceKeyTest extends TestCase
{
    #[DataProvider('paceValues')]
    public function testPaceTextAndBar(
        ?float $distance,
        int $duration,
        ?int $durationWithDistance,
        ?int $unit,
        string $expectedText,
        ?array $expectedBar
    ): void {
        $sport = $unit === null ? null : new Sport([
            Sport::INTERNAL_SPORT_ID => SportProfile::RUNNING,
            Sport::PACE_UNIT => $unit,
        ]);

        $context = new Context([
            Activity::DISTANCE => $distance,
            Activity::TIME_IN_SECONDS => $duration,
            Pace::DURATION_SUM_WITH_DISTANCE_KEY => $durationWithDistance,
        ], 0, $sport);

        $key = new Pace();
        $this->assertSame($expectedText, $key->stringFor($context));
        $this->assertSame($expectedBar, $key->valueDependendCssStyle($context));
    }

    public static function paceValues(): iterable
    {
        yield 'activity pace' => [5.0, 1800, null, PaceEnum::SECONDS_PER_KILOMETER, '6:00/km', [
            'class' => 'bar gray',
            'style' => '--perc: 62%;',
        ]];
        yield 'summary uses duration with distance' => [5.0, 1800, 1500, PaceEnum::KILOMETER_PER_HOUR, '12,0&nbsp;km/h', [
            'class' => 'bar blue',
            'style' => '--perc: 54%;',
        ]];
        yield 'mixed-sport summary falls back to km/h' => [5.0, 1800, 1500, null, '12,0&nbsp;km/h', null];
        yield 'unknown sport unit falls back to km/h' => [5.0, 1800, 1500, 999, '12,0&nbsp;km/h', [
            'class' => 'bar blue',
            'style' => '--perc: 54%;',
        ]];
        yield 'no distance' => [null, 1800, 1500, PaceEnum::SECONDS_PER_KILOMETER, '', null];
        yield 'no duration with distance' => [5.0, 1800, 0, PaceEnum::SECONDS_PER_KILOMETER, '', null];
        yield 'activity with zero duration' => [5.0, 0, null, PaceEnum::SECONDS_PER_KILOMETER, '-:--/km', null];
    }
}
