<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Plugins\Rechenspiele\RechenspielePanelPresenter;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrainingLoadCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrimpDataProvider;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class RechenspielePanelPresenterTest extends TestCase
{
    public function testPresentsTrainingLoadAndRespectsDisplaySettings(): void
    {
        $account = $this->account(12);
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

        $result = $this->presenter($repository, [
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
            'show_trimpvalues_extra' => '0',
        ], [
            'data.MAX_ATL' => '100',
            'data.MAX_CTL' => '100',
        ])->present($account, $this->installation($account));

        $this->assertSame(['Fatigue', 'Fitness level', 'Stress Balance', 'Rest days'], array_column($result['rows'], 'label'));
        $this->assertSame('27 %', $result['rows'][0]['value']);
        $this->assertSame('7 %', $result['rows'][1]['value']);
        $this->assertSame('-20', $result['rows'][2]['value']);
        $this->assertSame('red', $result['rows'][2]['tone']);
        $this->assertFalse($result['missing_vo2max']);
        $this->assertFalse($result['insufficient_activities']);
    }

    public function testRendersConfiguredVo2maxAndMarathonShapeWithoutModelRows(): void
    {
        $account = $this->account(12);
        $result = $this->presenter(null, [
            'show_trimpvalues' => '0',
            'show_trimpvalues_extra' => '0',
        ], [
            'data.VO2MAX_FORM' => '48.25',
            'data.BASIC_ENDURANCE' => '82',
        ])->present($account, $this->installation($account));

        $this->assertSame(['Effective VO2max', 'Marathon shape'], array_column($result['rows'], 'label'));
        $this->assertSame('48.25', $result['rows'][0]['value']);
        $this->assertSame('82 %', $result['rows'][1]['value']);
        $this->assertFalse($result['missing_vo2max']);
    }

    public function testAllowsAnEmptyTrainingHistoryWithoutErrors(): void
    {
        $account = $this->account(12);
        $result = $this->presenter(null, [
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
        ])->present($account, $this->installation($account));

        $this->assertSame('0 %', $result['rows'][0]['value']);
        $this->assertSame('0 %', $result['rows'][1]['value']);
        $this->assertSame('+0', $result['rows'][2]['value']);
    }

    public function testRespectsAbsoluteLoadAndPercentageBalanceConfiguration(): void
    {
        $account = $this->account(12);
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

        $result = $this->presenter($repository, [
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
            'show_trimpvalues_extra' => '0',
        ], [
            'trimp.TRIMP_MODEL_IN_PERCENT' => 'false',
            'trimp.TSB_IN_PERCENT' => 'true',
        ])->present($account, $this->installation($account));

        $this->assertSame('27', $result['rows'][0]['value']);
        $this->assertSame('7', $result['rows'][1]['value']);
        $this->assertSame('-74 %', $result['rows'][2]['value']);
    }

    public function testIndicatesMissingVo2maxAndRecentTrainingHistory(): void
    {
        $account = $this->account(12);
        $startTime = new DateTimeImmutable('2026-11-16 12:00:00 UTC')->getTimestamp();

        $result = $this->presenter(null, [
            'show_basicendurance' => '0',
        ], [
            'data.START_TIME' => (string) $startTime,
        ])->present($account, $this->installation($account));

        $this->assertTrue($result['missing_vo2max']);
        $this->assertTrue($result['insufficient_activities']);
    }

    public function testRejectsAnotherAccountsInstallationBeforeReadingTraining(): void
    {
        $account = $this->account(12);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findDailyTrimpForAccount');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter($repository)->present($account, $this->installation($this->account(13)));
    }

    public function testDeclaresModernPanelMetadata(): void
    {
        $presenter = $this->presenter();

        $this->assertSame('RunalyzePluginPanel_Rechenspiele', $presenter->getKey());
        $this->assertSame('Calculations', $presenter->getName());
        $this->assertSame('Calculate experimental values as shape and fatigue based on TRIMP, marathon shape and your VO<sub>2</sub>max shape.', $presenter->getDescription());
        $this->assertSame('@Rechenspiele/panel.html.twig', $presenter->getTemplate());
        $this->assertSame('@Rechenspiele/configuration.html.twig', $presenter->getConfigurationTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    public function testExposesConfigurationDataWithLegacyKeysAndDefaults(): void
    {
        $account = $this->account(12);

        $result = $this->presenter()->getConfigurationData($account, $this->installation($account));

        $this->assertTrue($result['show_trimpvalues']);
        $this->assertTrue($result['show_trimpvalues_extra']);
        $this->assertTrue($result['show_vo2max']);
        $this->assertTrue($result['show_basicendurance']);
        $this->assertSame(Plugin::STATE_ACTIVE, $result['active']);
    }

    public function testExposesConfiguredBooleanValuesAndActiveState(): void
    {
        $account = $this->account(12);
        $installation = $this->installation($account);
        $installation->method('getActive')->willReturn(Plugin::STATE_INACTIVE);

        $result = $this->presenter(null, [
            'show_trimpvalues' => '0',
            'show_trimpvalues_extra' => '1',
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
        ])->getConfigurationData($account, $installation);

        $this->assertFalse($result['show_trimpvalues']);
        $this->assertTrue($result['show_trimpvalues_extra']);
        $this->assertFalse($result['show_vo2max']);
        $this->assertFalse($result['show_basicendurance']);
        $this->assertSame(Plugin::STATE_INACTIVE, $result['active']);
    }

    public function testConfigurationDataRejectsAnotherAccountsInstallation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->presenter()->getConfigurationData($this->account(12), $this->installation($this->account(13)));
    }

    public function testPersistsSubmittedConfigurationValues(): void
    {
        $account = $this->account(12);
        $installation = $this->installation($account);
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([
            5 => [
                'show_trimpvalues' => '1',
            ],
        ]);
        $storage->expects($this->once())
            ->method('update')
            ->with(5, 'show_trimpvalues', '0');
        $inserted = [];
        $storage->expects($this->exactly(3))
            ->method('insert')
            ->willReturnCallback(static function (int $pluginId, string $key, string $value) use (&$inserted): void {
                $inserted[] = [$pluginId, $key, $value];
            });

        $this->presenter(null, [], [], $storage)->updateConfiguration($account, $installation, [
            'show_trimpvalues_extra' => '1',
        ]);

        $this->assertSame([
            [5, 'show_trimpvalues_extra', '1'],
            [5, 'show_vo2max', '0'],
            [5, 'show_basicendurance', '0'],
        ], $inserted);
    }

    public function testUpdateConfigurationRejectsAnotherAccountsInstallation(): void
    {
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('update');
        $storage->expects($this->never())->method('insert');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter(null, [], [], $storage)->updateConfiguration(
            $this->account(12),
            $this->installation($this->account(13)),
            ['show_vo2max' => '1']
        );
    }

    /**
     * @param array<string, string> $pluginOptions
     * @param array<string, string> $configurationOptions
     */
    private function presenter(
        ?TrainingRepository $repository = null,
        array $pluginOptions = [],
        array $configurationOptions = [],
        ?PluginConfigurationStorageInterface $storage = null
    ): RechenspielePanelPresenter {
        $repository ??= $this->createStub(TrainingRepository::class);
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-19 12:00:00 UTC'));

        $manager = $this->createStub(ConfigurationManager::class);
        $manager->method('getList')->willReturn(new RunalyzeConfigurationList($configurationOptions));

        $storage ??= $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([
            5 => $pluginOptions,
        ]);

        return new RechenspielePanelPresenter(
            new RechenspieleTrimpDataProvider($repository, $clock),
            new RechenspieleTrainingLoadCalculator(),
            $manager,
            $storage,
            $clock
        );
    }

    private function account(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);
        $account->method('getTimezone')->willReturn(Timezone::UTC);

        return $account;
    }

    private function installation(Account $account): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_Rechenspiele');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn(false);
        $installation->method('getActive')->willReturn(Plugin::STATE_ACTIVE);

        return $installation;
    }
}
