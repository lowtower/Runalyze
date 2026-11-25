<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Plugins\Rechenspiele\RechenspieleExplanationWindowProvider;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrimpDataProvider;
use Runalyze\Sports\Running\MarathonShape;
use Runalyze\Sports\Running\Prognosis\VO2max as Vo2maxPrognosis;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class RechenspieleExplanationWindowProviderTest extends TestCase
{
    public function testTrimpOverviewComparesModelValuesToStoredMaxima(): void
    {
        $account = $this->account();
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findDailyTrimpForAccount')->willReturn([
            [
                'day' => '2026-11-17',
                'trimp' => 100.0,
            ],
            [
                'day' => '2026-11-19',
                'trimp' => 50.0,
            ],
        ]);

        $result = $this->provider($repository, [
            'data.MAX_ATL' => '100',
            'data.MAX_CTL' => '100',
            'trimp.ATL_DAYS' => '7',
            'trimp.CTL_DAYS' => '42',
        ])->getTrimpOverview($account);

        $this->assertTrue($result['has_history']);
        $this->assertSame(6, $result['atl']);
        $this->assertSame(5, $result['ctl']);
        $this->assertSame(7, $result['atl_days']);
        $this->assertSame(42, $result['ctl_days']);
        $this->assertSame(100, $result['atl_max']);
        $this->assertSame(100, $result['ctl_max']);
        $this->assertSame($result['ctl_absolute'] . ' - ' . $result['atl_absolute'] . ' = ' . $result['tsb'], $result['tsb_equation']);
    }

    public function testTrimpOverviewWorksWithoutTrainingHistory(): void
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findDailyTrimpForAccount')->willReturn([]);

        $result = $this->provider($repository, [
            'data.MAX_ATL' => '100',
            'data.MAX_CTL' => '100',
        ])->getTrimpOverview(new Account());

        $this->assertFalse($result['has_history']);
        $this->assertSame(0, $result['atl']);
        $this->assertSame(0, $result['ctl']);
        $this->assertSame('0.00 - 0.00 = 0.00', $result['tsb_equation']);
    }

    public function testVo2maxOverviewListsAccountEffectiveValues(): void
    {
        $account = $this->account();
        $from = new DateTimeImmutable('2026-10-25 12:00:00 UTC')->getTimestamp();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findEffectiveVo2maxHistoryFor')
            ->with($account, false, $from)
            ->willReturn([
                [
                    'id' => 3,
                    'time' => new DateTimeImmutable('2026-11-23 08:30:00 UTC')->getTimestamp(),
                    'distance' => 21.1,
                    'vo2max' => 60.0,
                ],
                [
                    'id' => 7,
                    'time' => new DateTimeImmutable('2026-11-24 07:00:00 UTC')->getTimestamp(),
                    'distance' => 10.0,
                    'vo2max' => 50.0,
                ],
            ]);

        $result = $this->provider($repository, [
            'vo2max.VO2MAX_MANUAL_VALUE' => '55',
        ])->getEffectiveVo2maxOverview($account);

        $this->assertSame('55.00', $result['current']);
        $this->assertSame(30, $result['days']);
        $this->assertSame('km', $result['unit']);
        $this->assertSame([
            [
                'id' => 3,
                'date' => '23.11.2026',
                'distance' => '21.10',
                'vo2max' => '60.00',
                'tooltip' => '23.11.2026: 21.10',
            ],
            [
                'id' => 7,
                'date' => '24.11.2026',
                'distance' => '10.00',
                'vo2max' => '50.00',
                'tooltip' => '24.11.2026: 10.00',
            ],
        ], $result['values']);
    }

    public function testVo2maxOverviewPassesElevationConfigurationToRepository(): void
    {
        $account = $this->account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findEffectiveVo2maxHistoryFor')
            ->with($account, true, $this->isInt())
            ->willReturn([]);

        $result = $this->provider($repository, [
            'vo2max.VO2MAX_USE_CORRECTION_FOR_ELEVATION' => 'true',
        ])->getEffectiveVo2maxOverview($account);

        $this->assertSame([], $result['values']);
    }

    public function testBasicEnduranceOverviewComputesTargetsAndLongJogPoints(): void
    {
        $account = $this->account();
        $configurationList = new RunalyzeConfigurationList([
            'data.VO2MAX_FORM' => '48.25',
            'data.START_TIME' => (string) new DateTimeImmutable('2026-01-01 00:00:00 UTC')->getTimestamp(),
            'general.RUNNINGSPORT' => '7',
        ]);
        $basicEndurance = $configurationList->getBasicEndurance();
        $end = new DateTimeImmutable('2026-11-24 23:59:59 UTC')->getTimestamp();
        $usedVO2max = 48.25;
        $targetWeekKm = $usedVO2max ** 1.135;
        $targetLongJogKm = max(0.1, new MarathonShape($usedVO2max, $basicEndurance)->getTargetForLongJogEachWeek() - 13.0);
        $longJogDays = $basicEndurance->getDaysToConsiderForLongJogs();
        $weekDays = $basicEndurance->getDaysToConsiderForWeeklyMileage(327);

        $runOne = new DateTimeImmutable('2026-11-04 12:00:00 UTC')->getTimestamp();
        $runTwo = new DateTimeImmutable('2026-11-23 09:00:00 UTC')->getTimestamp();
        $pointsOne = (2 - 2 / $longJogDays * floor(($end - $runOne) / 86400)) * (30.0 - 13.0) ** 2 / $targetLongJogKm ** 2;
        $pointsTwo = (2 - 2 / $longJogDays * floor(($end - $runTwo) / 86400)) * (14.0 - 13.0) ** 2 / $targetLongJogKm ** 2;

        $this->assertGreaterThanOrEqual(0.2, $pointsOne);
        $this->assertLessThan(0.2, $pointsTwo);

        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findBasicEnduranceRunsFor')
            ->with($account, 7, $end - max($weekDays, $longJogDays) * 86400, $end + 1)
            ->willReturn([
                [
                    'id' => 11,
                    'time' => $runOne,
                    'distance' => 30.0,
                ],
                [
                    'id' => 12,
                    'time' => $runTwo,
                    'distance' => 14.0,
                ],
            ]);

        $provider = new RechenspieleExplanationWindowProvider(
            new RechenspieleTrimpDataProvider($this->createStub(TrainingRepository::class), $this->clock()),
            $repository,
            $this->configurationManager($configurationList),
            $this->clock()
        );

        $result = $provider->getBasicEnduranceOverview($account);

        $kilometers = 44.0;
        $weekPercentage = $kilometers * 7 / $weekDays / $targetWeekKm;
        $longJogPercentage = ($pointsOne + $pointsTwo) * 7 / $longJogDays;

        $this->assertSame('48.25', $result['vo2max']);
        $this->assertTrue($result['has_vo2max']);
        $this->assertSame('km', $result['unit']);
        $this->assertSame((int) round(100 * ($weekPercentage * $basicEndurance->getPercentageForWeeklyMileage() + $longJogPercentage * $basicEndurance->getPercentageForLongJogs())), $result['percentage']);
        $this->assertSame((int) round(100 * $weekPercentage), $result['week_percentage']);
        $this->assertSame(number_format($targetWeekKm, 2), $result['week_target_km']);
        $this->assertSame(number_format($kilometers / $weekDays * 7, 2), $result['week_avg_km']);
        $this->assertSame(number_format(round($pointsOne + $pointsTwo, 1), 1), $result['longjog_points']);
        $this->assertSame((int) round(100 * $longJogPercentage), $result['longjog_percentage']);
        $this->assertSame(number_format($targetLongJogKm + 13.0, 2), $result['longjog_target_km']);
        $this->assertSame(gmdate('H:i:s', (int) new Vo2maxPrognosis($usedVO2max)->getSeconds(42.195)), $result['marathon_time']);
        $this->assertSame('13.00', $result['minimal_longjog_km']);
        $this->assertSame(1, $result['ignored_runs']);
        $this->assertSame([
            [
                'id' => 11,
                'date' => '04.11.2026',
                'distance' => '30.00',
                'points' => number_format(round($pointsOne, 1), 1),
            ],
        ], $result['runs']);
    }

    public function testBasicEnduranceOverviewHandlesAMissingVo2max(): void
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findBasicEnduranceRunsFor')->willReturn([]);

        $result = $this->provider($repository, [
            'general.RUNNINGSPORT' => '7',
        ])->getBasicEnduranceOverview(new Account());

        $this->assertSame('0.00', $result['vo2max']);
        $this->assertFalse($result['has_vo2max']);
        $this->assertSame(0, $result['percentage']);
        $this->assertSame(gmdate('H:i:s', (int) new Vo2maxPrognosis(25.0)->getSeconds(42.195)), $result['marathon_time']);
    }

    public function testWindowDataCombinesAllOverviews(): void
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findDailyTrimpForAccount')->willReturn([]);
        $repository->method('findEffectiveVo2maxHistoryFor')->willReturn([]);
        $repository->method('findBasicEnduranceRunsFor')->willReturn([]);

        $result = $this->provider($repository, [
            'general.RUNNINGSPORT' => '7',
        ])->getWindowData(new Account());

        $this->assertSame(['trimp', 'vo2max', 'basic_endurance'], array_keys($result));
    }

    /**
     * @param array<string, string> $options
     */
    private function provider(?TrainingRepository $repository = null, array $options = []): RechenspieleExplanationWindowProvider
    {
        return new RechenspieleExplanationWindowProvider(
            new RechenspieleTrimpDataProvider($repository ?? $this->createStub(TrainingRepository::class), $this->clock()),
            $repository ?? $this->createStub(TrainingRepository::class),
            $this->configurationManager(new RunalyzeConfigurationList($options)),
            $this->clock()
        );
    }

    private function configurationManager(RunalyzeConfigurationList $configurationList): ConfigurationManager
    {
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->willReturn($configurationList);

        return $configurationManager;
    }

    private function clock(): ClockInterface
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-24 12:00:00 UTC'));

        return $clock;
    }

    private function account(): Account
    {
        $account = new Account();
        $account->setTimezone(Timezone::UTC);

        return $account;
    }
}
