<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Box;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Dataview;
use SectionPaceRow;

final class AveragePaceTest extends TestCase
{
    #[DataProvider('units')]
    public function testActivityPaceRowUsesTheSportUnitAndItsComplement(string $system, int $sportUnit, array $expected): void
    {
        $activity = new Activity([
            Activity::DISTANCE => 5.0,
            Activity::TIME_IN_SECONDS => 1500,
        ]);
        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn($activity);
        $context->method('dataview')->willReturn(new Dataview($activity));
        $context->method('sport')->willReturn(new Sport([
            Sport::PACE_UNIT => $sportUnit,
        ]));
        $context->method('trackdata')->willReturn(new Trackdata());

        $distanceUnitSystem = Configuration::General()->distanceUnitSystem();
        $previous = $distanceUnitSystem->valueAsString();
        $distanceUnitSystem->set($system);

        try {
            $boxes = new SectionPaceRow($context)->templateData()['info']['boxed_values'];
        } finally {
            $distanceUnitSystem->set($previous);
        }

        $this->assertSame($expected, array_map(
            static fn (array $box): array => [$box['value'], $box['unit']],
            $boxes
        ));
    }

    public static function units(): iterable
    {
        yield 'metric speed complement' => [DistanceUnitSystem::METRIC, PaceEnum::SECONDS_PER_KILOMETER, [
            ['5:00', '/km'],
            ['12,0', '&nbsp;km/h'],
        ]];
        yield 'imperial speed complement' => [DistanceUnitSystem::IMPERIAL, PaceEnum::SECONDS_PER_MILE, [
            ['8:03', '/mi'],
            ['7,5', '&nbsp;mph'],
        ]];
        yield 'metric pace complement' => [DistanceUnitSystem::METRIC, PaceEnum::KILOMETER_PER_HOUR, [
            ['12,0', '&nbsp;km/h'],
            ['5:00', '/km'],
        ]];
        yield 'imperial pace complement' => [DistanceUnitSystem::IMPERIAL, PaceEnum::MILES_PER_HOUR, [
            ['7,5', '&nbsp;mph'],
            ['8:03', '/mi'],
        ]];
    }
}
