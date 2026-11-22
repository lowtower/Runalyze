<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Parameter\Application\PaceUnit;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPredictionRows;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;

#[Group('plugin')]
final class PrognoseCalculatorPredictionRowsTest extends TestCase
{
    public function testRendersPredictionAndLinkedPersonalBestForCustomDistance(): void
    {
        $account = $this->account();
        $raceResults = $this->createMock(RaceresultRepository::class);
        $raceResults->expects($this->once())
            ->method('findPersonalBestDetailsForDistances')
            ->with($account, 3, [5.72])
            ->willReturn([
                '5.72' => [
                    'activity_id' => 51,
                    'seconds' => 2064.0,
                ],
            ]);

        $activity = $this->createStub(Training::class);
        $activity->method('getTime')->willReturn(1700000000);

        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->once())
            ->method('findForAccount')
            ->with(51, 12)
            ->willReturn($activity);

        $rows = $this->calculator($raceResults, $trainings)->calculate(
            $account,
            3,
            [5.72],
            $this->prognosis(2100.0),
            PaceUnit::MIN_PER_KM,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC)
        );

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('5.72', $rows[0]['distance']);
        $this->assertSame('35:00', $rows[0]['prognosis']);
        $this->assertSame('34:24', $rows[0]['pb']);
        $this->assertSame('- 0:36', $rows[0]['diff']);
        $this->assertSame('minus', $rows[0]['diff-class']);
        $this->assertStringContainsString('/km', $rows[0]['prognosis-pace']);
        $this->assertStringContainsString('activity/51', $rows[0]['pb-date']);
        $this->assertStringContainsString('Runalyze.Overlay.close()', $rows[0]['pb-date']);
    }

    public function testRendersPredictionsWithoutPersonalBests(): void
    {
        $raceResults = $this->createMock(RaceresultRepository::class);
        $raceResults->expects($this->once())
            ->method('findPersonalBestDetailsForDistances')
            ->willReturn([]);
        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->never())->method('findForAccount');

        $rows = $this->calculator($raceResults, $trainings)->calculate(
            $this->account(),
            3,
            [10.0],
            $this->prognosis(2400.0),
            PaceUnit::MIN_PER_MILE,
            new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL)
        );

        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0]['pb']);
        $this->assertSame('-', $rows[0]['pb-pace']);
        $this->assertSame('-', $rows[0]['pb-vo2max']);
        $this->assertSame('-', $rows[0]['pb-date']);
        $this->assertSame('-', $rows[0]['diff']);
        $this->assertStringContainsString('mi', $rows[0]['distance']);
        $this->assertStringContainsString('/mi', $rows[0]['prognosis-pace']);
    }

    public function testReturnsNoRowsOrDatabaseQueriesForInvalidPrognosis(): void
    {
        $raceResults = $this->createMock(RaceresultRepository::class);
        $raceResults->expects($this->never())->method('findPersonalBestDetailsForDistances');
        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->never())->method('findForAccount');

        $prognosis = $this->createStub(PrognosisInterface::class);
        $prognosis->method('areValuesValid')->willReturn(false);

        $this->assertSame([], $this->calculator($raceResults, $trainings)->calculate(
            $this->account(),
            3,
            [5.72],
            $prognosis,
            PaceUnit::MIN_PER_KM,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC)
        ));
    }

    public function testRendersNoPredictionForNullPrognosisSeconds(): void
    {
        $raceResults = $this->createMock(RaceresultRepository::class);
        $raceResults->expects($this->once())
            ->method('findPersonalBestDetailsForDistances')
            ->willReturn([]);
        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->never())->method('findForAccount');

        $rows = $this->calculator($raceResults, $trainings)->calculate(
            $this->account(),
            3,
            [5.72],
            $this->prognosis(null),
            PaceUnit::MIN_PER_KM,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC)
        );

        $this->assertSame('-', $rows[0]['prognosis']);
        $this->assertSame('-', $rows[0]['prognosis-vo2max']);
        $this->assertSame('-', $rows[0]['diff']);
    }

    private function account(): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn(12);

        return $account;
    }

    private function prognosis(?float $seconds): PrognosisInterface
    {
        $prognosis = $this->createStub(PrognosisInterface::class);
        $prognosis->method('areValuesValid')->willReturn(true);
        $prognosis->method('getSeconds')->willReturn($seconds);

        return $prognosis;
    }

    private function calculator(
        RaceresultRepository $raceResults,
        TrainingRepository $trainings
    ): PrognoseCalculatorPredictionRows {
        return new PrognoseCalculatorPredictionRows(
            new PrognoseCalculatorPersonalBestProvider($raceResults, $trainings)
        );
    }
}
