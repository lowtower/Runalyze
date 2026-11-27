<?php

declare(strict_types=1);

namespace Runalyze\View\Window\Laps;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Parser\Activity\FileType\FitWorkoutStep;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Dataview;

final class WindowPaceTest extends TestCase
{
    #[DataProvider('sportUnits')]
    public function testDisplaysPaceGoalLapAndFitTargetInSportUnit(
        int $unit,
        string $lap,
        string $difference,
        string $target,
        string $average
    ): void {
        $targetKey = FitWorkoutStep::ADD_TARGET_SPEED . ' low';
        $activity = new Activity([
            Activity::DISTANCE => 2.0,
            Activity::TIME_IN_SECONDS => 590,
            Activity::SPLITS => '',
            Activity::SPLITS_ADDITIONAL => json_encode([
                'keys' => [$targetKey],
                'data' => [
                    [
                        $targetKey => 3,
                    ],
                    [
                        $targetKey => 4,
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        $dataview = $this->createStub(Dataview::class);
        $dataview->method('titleWithComment')->willReturn('Lap pace test');

        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn($activity);
        $context->method('sport')->willReturn(new Sport([
            Sport::PACE_UNIT => $unit,
        ]));
        $context->method('trackdata')->willReturn(new Trackdata([
            Trackdata::TIME => [0, 280, 590],
            Trackdata::DISTANCE => [0, 1, 2],
        ]));
        $context->method('dataview')->willReturn($dataview);

        $post = $_POST;
        $_POST = [
            'distance' => '1',
            'demanded-pace' => '5:00',
        ];

        ob_start();
        try {
            new Window($context)->display();
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_POST = $post;
        }

        $this->assertStringContainsString('<td>' . $lap . '</td>', $html);
        $this->assertStringContainsString('<td><span class="plus">+' . $difference . '</span></td>', $html);
        $this->assertStringContainsString('<td>' . $target . '</td>', $html);
        $this->assertStringContainsString('<td class="c">' . $average . '</td>', $html);
    }

    public static function sportUnits(): iterable
    {
        yield 'pace' => [PaceEnum::SECONDS_PER_KILOMETER, '4:40/km', '0:20/km', '5:33/km', '4:55/km'];
        yield 'speed' => [PaceEnum::KILOMETER_PER_HOUR, '12,9&nbsp;km/h', '0,9&nbsp;km/h', '10,8&nbsp;km/h', '12,2&nbsp;km/h'];
    }
}
