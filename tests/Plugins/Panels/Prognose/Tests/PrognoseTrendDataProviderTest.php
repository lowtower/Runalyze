<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendDataProvider;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendSeriesCalculator;
use Runalyze\Sports\Running\Prognosis\VO2max;

#[Group('plugin')]
final class PrognoseTrendDataProviderTest extends TestCase
{
    public function testReturnsEmptySeriesForAccountWithoutTrainingHistory(): void
    {
        $account = new Account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findPrognosisTrendHistory')
            ->with($account, 4, true)
            ->willReturn([]);

        $configuration = new RunalyzeConfigurationList([
            'vo2max.VO2MAX_USE_CORRECTION_FOR_ELEVATION' => 'true',
        ]);

        $provider = new PrognoseTrendDataProvider(
            $repository,
            $this->configurationManager($account, $configuration),
            new PrognoseTrendSeriesCalculator()
        );

        $this->assertSame([
            'has_history' => false,
            'prognosis' => [],
            'prognosis_with_basic_endurance' => [],
        ], $provider->getSeries($account, 4, 10.0));
    }

    public function testUsesAccountConfigurationForVO2maxPrediction(): void
    {
        $account = new Account();
        $today = date('Y-m-d');
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findPrognosisTrendHistory')
            ->with($account, 4, false)
            ->willReturn([[
                'date_age' => 0,
                'date' => $today,
                'distance' => 10.0,
                'vo2max_weighted' => 45.0 * 3600,
                'vo2max_sum_time' => 3600.0,
            ]]);

        $configuration = new RunalyzeConfigurationList([
            'vo2max.VO2MAX_DAYS' => '30',
            'vo2max.VO2MAX_MANUAL_CORRECTOR' => '1.1111111111111112',
            'basic-endurance.BE_DAYS_FOR_WEEK_KM' => '60',
            'basic-endurance.BE_DAYS_FOR_WEEK_KM_MIN' => '30',
            'basic-endurance.BE_DAYS_FOR_LONGJOGS' => '40',
            'basic-endurance.BE_MIN_KM_FOR_LONGJOG' => '10',
            'basic-endurance.BE_PERCENTAGE_WEEK_KM' => '0.5',
        ]);

        $provider = new PrognoseTrendDataProvider(
            $repository,
            $this->configurationManager($account, $configuration),
            new PrognoseTrendSeriesCalculator()
        );

        $series = $provider->getSeries($account, 4, 10.0);
        $plotKey = strtotime($today . ' 12:00') . '000';
        $expected = VO2max::getPrognosisInSecondsFor(50.0, 10.0);

        $this->assertNotNull($expected);
        $this->assertTrue($series['has_history']);
        $this->assertArrayHasKey($plotKey, $series['prognosis']);
        $this->assertArrayHasKey($plotKey, $series['prognosis_with_basic_endurance']);
        $this->assertEqualsWithDelta($expected * 1000, $series['prognosis'][$plotKey], 0.1);
    }

    private function configurationManager(Account $account, RunalyzeConfigurationList $list): ConfigurationManager
    {
        $manager = $this->createMock(ConfigurationManager::class);
        $manager->expects($this->once())->method('getList')->with($account)->willReturn($list);

        return $manager;
    }
}
