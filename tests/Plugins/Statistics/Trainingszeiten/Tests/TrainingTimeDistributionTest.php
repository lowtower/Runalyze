<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Trainingszeiten\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Plugins\Statistics\Trainingszeiten\TrainingTimeDistribution;

/**
 * @phpstan-import-type TrainingTimeSample from TrainingRepository
 */
#[Group('plugin')]
final class TrainingTimeDistributionTest extends TestCase
{
    public function testNightListIsLimitedToTheTwentyActivitiesNearestTwoAm(): void
    {
        $twoAm = strtotime('2025-01-06 02:00:00 UTC');
        $activities = [];
        for ($minute = 20; $minute >= 0; --$minute) {
            $activities[] = $this->activity($minute + 1, $twoAm + 60 * $minute, 60.0);
        }

        $data = new TrainingTimeDistribution()->summarize([
            1 => 'Running',
        ], $activities, true, false);
        $this->assertSame(range(1, 20), array_column($data['nights'], 'id'));
    }

    public function testDaytimeDistributionIncludesEveryHourOfAnActivityLongerThanOneDay(): void
    {
        $activity = $this->activity(1, strtotime('2025-01-06 23:30:00 UTC'), 25 * 3600.0);
        $data = new TrainingTimeDistribution()->summarize([
            1 => 'Running',
        ], [$activity], false, false);

        $this->assertEqualsWithDelta(1.5, $data['daytime'][1][23], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.5, $data['daytime'][1][0], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(25.0, array_sum($data['daytime'][1]), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(25.0, $data['weekday'][1][0], PHP_FLOAT_EPSILON);
        $this->assertSame([], $data['nights']);
    }

    /**
     * @return TrainingTimeSample
     */
    private function activity(int $id, int $time, float $duration): array
    {
        return [
            'id' => $id,
            'time' => $time,
            'sportId' => 1,
            'duration' => $duration,
            'distance' => 0.0,
            'isTrack' => false,
            'shortSport' => false,
            'sportIcon' => '',
        ];
    }
}
