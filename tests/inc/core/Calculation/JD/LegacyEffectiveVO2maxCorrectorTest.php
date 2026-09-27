<?php

declare(strict_types=1);

namespace Runalyze\Tests\Calculation\JD;

use Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector;
use Runalyze\Model\Activity;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class LegacyEffectiveVO2maxCorrectorTest extends \PHPUnit\Framework\TestCase
{
    protected \PDOforRunalyze $DB;

    protected function setUp(): void
    {
        \SessionAccountHandler::setAccount([
            'id' => 0,
            'username' => 'runalyze',
            'language' => 'de',
            'timezone' => '43',
            'mail' => 'noreply@runalyze.com',
        ]);

        $this->DB = \DB::getInstance();
        $this->DB->setAccountID(0);

        $this->DB->exec('DELETE FROM `runalyze_raceresult`');
        $this->DB->exec('DELETE FROM `runalyze_training`');

        LegacyEffectiveVO2maxCorrector::setGlobalFactor(1.0);
    }

    protected function tearDown(): void
    {
        $this->DB->exec('DELETE FROM `runalyze_raceresult`');
        $this->DB->exec('DELETE FROM `runalyze_training`');

        LegacyEffectiveVO2maxCorrector::setGlobalFactor(1.0);
    }

    public function testConstructorUsesDefaultGlobalFactor(): void
    {
        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertSame(1.0, $corrector->factor());
    }

    public function testConstructorUsesConfiguredGlobalFactor(): void
    {
        LegacyEffectiveVO2maxCorrector::setGlobalFactor(1.25);

        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertSame(1.25, $corrector->factor());
    }

    public function testConstructorUsesExplicitFactorInsteadOfGlobalFactor(): void
    {
        LegacyEffectiveVO2maxCorrector::setGlobalFactor(1.25);

        $corrector = new LegacyEffectiveVO2maxCorrector(1.5);

        $this->assertSame(1.5, $corrector->factor());
    }

    public function testFactorIsCalculatedFromActivity(): void
    {
        $activity = new Activity\Entity([
            Activity\Entity::VO2MAX => 50.0,
            Activity\Entity::VO2MAX_BY_TIME => 60.0,
        ]);

        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertEqualsWithDelta(
            1.2,
            $corrector->fromActivity($activity),
            0.0001
        );

        $this->assertEqualsWithDelta(
            1.2,
            $corrector->factor(),
            0.0001
        );
    }

    public function testFactorFromActivityFallsBackToOneWithoutHeartRateVo2max(): void
    {
        $activity = new Activity\Entity([
            Activity\Entity::VO2MAX => 0.0,
            Activity\Entity::VO2MAX_BY_TIME => 60.0,
        ]);

        $corrector = new LegacyEffectiveVO2maxCorrector(1.5);

        $this->assertSame(
            1.0,
            $corrector->fromActivity($activity)
        );

        $this->assertSame(1.0, $corrector->factor());
    }

    public function testFactorIsCalculatedFromDatabase(): void
    {
        $sportId = \Runalyze\Configuration::General()->runningSport();

        $firstActivityId = $this->insertActivity(
            $sportId,
            50.0,
            55.0
        );

        $secondActivityId = $this->insertActivity(
            $sportId,
            50.0,
            60.0
        );

        $thirdActivityId = $this->insertActivity(
            $sportId,
            50.0,
            57.5
        );

        $this->insertRaceResult($firstActivityId);
        $this->insertRaceResult($secondActivityId);
        $this->insertRaceResult($thirdActivityId);

        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertEqualsWithDelta(
            1.2,
            $corrector->fromDatabase(
                $this->DB,
                \SessionAccountHandler::getId(),
                $sportId
            ),
            0.0001
        );

        $this->assertEqualsWithDelta(
            1.2,
            $corrector->factor(),
            0.0001
        );
    }

    public function testDatabaseLookupOnlyConsidersConfiguredSport(): void
    {
        $runningSportId = \Runalyze\Configuration::General()->runningSport();
        $otherSportId = $runningSportId + 1000;

        $runningActivityId = $this->insertActivity(
            $runningSportId,
            50.0,
            55.0
        );

        $otherActivityId = $this->insertActivity(
            $otherSportId,
            50.0,
            100.0
        );

        $this->insertRaceResult($runningActivityId);
        $this->insertRaceResult($otherActivityId);

        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertEqualsWithDelta(
            1.1,
            $corrector->fromDatabase(
                $this->DB,
                \SessionAccountHandler::getId(),
                $runningSportId
            ),
            0.0001
        );
    }

    public function testDatabaseLookupIgnoresActivitiesWithoutUsableVo2max(): void
    {
        $sportId = \Runalyze\Configuration::General()->runningSport();

        $validActivityId = $this->insertActivity(
            $sportId,
            50.0,
            55.0,
            1
        );

        $ignoredActivityId = $this->insertActivity(
            $sportId,
            50.0,
            100.0,
            0
        );

        $this->insertRaceResult($validActivityId);
        $this->insertRaceResult($ignoredActivityId);

        $corrector = new LegacyEffectiveVO2maxCorrector();

        $this->assertEqualsWithDelta(
            1.1,
            $corrector->fromDatabase(
                $this->DB,
                \SessionAccountHandler::getId(),
                $sportId
            ),
            0.0001
        );
    }

    public function testDatabaseLookupFallsBackToOneWithoutMatchingRace(): void
    {
        $corrector = new LegacyEffectiveVO2maxCorrector(1.5);

        $result = $corrector->fromDatabase(
            $this->DB,
            \SessionAccountHandler::getId(),
            \Runalyze\Configuration::General()->runningSport()
        );

        $this->assertSame(1.0, $result);
        $this->assertSame(1.0, $corrector->factor());
    }

    private function insertActivity(
        int $sportId,
        float $vo2max,
        float $vo2maxByTime,
        int $useVo2max = 1
    ): int {
        return (int) $this->DB->insert(
            'training',
            [
                'time',
                'distance',
                'sportid',
                's',
                'vo2max',
                'vo2max_by_time',
                'use_vo2max',
            ],
            [
                time(),
                10.0,
                $sportId,
                3600,
                $vo2max,
                $vo2maxByTime,
                $useVo2max,
            ]
        );
    }

    private function insertRaceResult(int $activityId): int
    {
        return (int) $this->DB->insert(
            'raceresult',
            [
                'activity_id',
                'official_time',
            ],
            [
                $activityId,
                3600,
            ]
        );
    }
}
