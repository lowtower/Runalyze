<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Swimdata\Entity as Swimdata;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;

final class PaceTablesTest extends TestCase
{
    public function testPaceGoalRowsRetainPacesAndSignedDifferences(): void
    {
        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn(new Activity([
            Activity::DISTANCE => 0.2,
            Activity::TIME_IN_SECONDS => 110,
            Activity::PACE_GOAL => json_encode([
                [
                    'pp_dist_m' => 100,
                    'pp_t_s' => 45,
                    'dist_m' => 100,
                    't_s' => 50,
                ],
                [
                    'pp_dist_m' => 100,
                    'pp_t_s' => 65,
                    'dist_m' => 100,
                    't_s' => 60,
                ],
            ], JSON_THROW_ON_ERROR),
        ]));

        $bodies = new TablePaceGoal($context)->templateData()['bodies'];
        $rows = $bodies[0]['rows'];
        $summary = $bodies[1]['rows'][0];

        $this->assertSame('7:30/km', $rows[0]['cells'][7]['content']);
        $this->assertSame('8:20/km', $rows[0]['cells'][8]['content']);
        $this->assertSame('<span class="minus">-0:50/km</span>', $rows[0]['cells'][9]['content']);
        $this->assertSame('<span class="plus">+0:50/km</span>', $rows[1]['cells'][9]['content']);
        $this->assertSame('<span class="plus">+0:00/km</span>', $summary['cells'][9]['content']);
    }

    public function testSwimmingIntervalsRetainPaceBarAndDifference(): void
    {
        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn(new Activity([
            Activity::SPLITS => '',
        ]));
        $context->method('trackdata')->willReturn(new Trackdata([
            Trackdata::TIME => [0, 50, 110],
            Trackdata::DISTANCE => [0.0, 0.1, 0.2],
        ]));
        $context->method('swimdata')->willReturn(new Swimdata([
            Swimdata::POOL_LENGTH => 10000,
            Swimdata::STROKE => [25, 20, 20],
            Swimdata::STROKETYPE => [0, 0, 0],
            Swimdata::SWOLF => [75, 70, 80],
        ]));

        $rows = new TableSwimLaneAggregateInterval($context)->templateData()['bodies'][0]['rows'];

        $this->assertSame('0:50/100m', $rows[0]['cells'][3]['content']);
        $this->assertSame('', $rows[0]['cells'][5]['content']);
        $this->assertSame('1:00/100m', $rows[1]['cells'][3]['content']);
        $this->assertSame('<span class="bar-chart-value" style="width:60px; top: 0px;"></span>', $rows[1]['cells'][4]['content']);
        $this->assertSame('<span style="opacity: 1.0"><span class="minus">-0:10/100m</span></span>', $rows[1]['cells'][5]['content']);
    }
}
