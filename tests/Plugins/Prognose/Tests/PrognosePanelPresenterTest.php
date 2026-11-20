<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Metrics\Distance\Unit\Kilometer;
use Runalyze\Metrics\Distance\Unit\Miles;
use Runalyze\Plugins\Prognose\PrognosePanelPresenter;

#[Group('plugin')]
final class PrognosePanelPresenterTest extends TestCase
{
    public function testExposesLegacyDefaultValuesForMetricConfiguration(): void
    {
        $account = $this->account(12);
        $presenter = $this->presenter([], new Kilometer());

        $this->assertSame('RunalyzePluginPanel_Prognose', $presenter->getKey());
        $this->assertSame('Prognosis', $presenter->getName());
        $this->assertSame('@Prognose/configuration.html.twig', $presenter->getConfigurationTemplate());

        $data = $presenter->getConfigurationData($account, $this->installation($account));

        $this->assertSame('1, 3, 5, 10, 21.1, 42.2', $data['distances']);
        $this->assertSame('km', $data['distance_unit']);
        $this->assertSame('vo2max', $data['model']);
        $this->assertTrue($data['use_be']);
    }

    public function testConvertsStoredKilometersToMilesForConfigurationDisplay(): void
    {
        $account = $this->account(12);
        $presenter = $this->presenter([
            5 => [
                'distances' => '5,10',
            ],
        ], new Miles());

        $data = $presenter->getConfigurationData($account, $this->installation($account));

        $this->assertSame('3.11, 6.21', $data['distances']);
        $this->assertSame('mi', $data['distance_unit']);
    }

    public function testPredictionDistancesUseLegacyDefaultsWithoutStoredConfiguration(): void
    {
        $account = $this->account(12);
        $presenter = $this->presenter([], new Kilometer());

        $this->assertSame(
            [1, 3, 5, 10, 21.1, 42.2],
            $presenter->getPredictionDistances($account, $this->installation($account))
        );
    }

    public function testPredictionDistancesReadStoredKilometersForCalculator(): void
    {
        $account = $this->account(12);
        $presenter = $this->presenter([
            5 => ['distances' => '5, 10, invalid, -2, 21.1'],
        ], new Miles());

        $this->assertSame(
            [5.0, 10.0, 21.1],
            $presenter->getPredictionDistances($account, $this->installation($account))
        );
    }

    public function testPredictionDistancesRejectAnotherAccountsInstallationWithoutReadingStorage(): void
    {
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter([], new Kilometer(), $storage)
            ->getPredictionDistances($this->account(12), $this->installation($this->account(13)));
    }

    public function testPredictionDistancesAcceptInactiveInstallationForCalculatorSettings(): void
    {
        $account = $this->account(12);
        $presenter = $this->presenter([
            5 => ['distances' => '3, 7'],
        ], new Kilometer());

        $this->assertSame([3.0, 7.0], $presenter->getPredictionDistances($account, $this->installation($account, true)));
    }

    public function testConvertsSubmittedMilesToKilometersForPersistence(): void
    {
        $account = $this->account(12);
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())->method('loadForPlugins')->with([5])->willReturn([]);
        $storage->expects($this->exactly(3))
            ->method('insert')
            ->willReturnCallback(static function (int $id, string $key, string $value): void {
                self::assertSame(5, $id);
                self::assertContains([$key, $value], [
                    ['distances', '4.99,10.01'],
                    ['model', 'cpp'],
                    ['use_be', '0'],
                ]);
            });
        $storage->expects($this->never())->method('update');

        $this->presenter([], new Miles(), $storage)->updateConfiguration(
            $account,
            $this->installation($account),
            [
                'edit' => 'true',
                'distances' => '3.1, 6.22',
                'model' => 'cpp',
            ]
        );
    }

    public function testUpdatesExistingConfigurationValues(): void
    {
        $account = $this->account(12);
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())->method('loadForPlugins')->willReturn([
            5 => [
                'distances' => '5,10',
                'model' => 'vo2max',
                'use_be' => '1',
            ],
        ]);
        $storage->expects($this->exactly(3))->method('update');
        $storage->expects($this->never())->method('insert');

        $this->presenter([], new Kilometer(), $storage)->updateConfiguration(
            $account,
            $this->installation($account, true),
            [
                'edit' => 'true',
                'distances' => '3, 10',
                'model' => 'cameron',
                'use_be' => '1',
            ]
        );
    }

    public function testRejectsInvalidConfigurationValuesWithoutPersistence(): void
    {
        $account = $this->account(12);
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())->method('loadForPlugins')->willReturn([]);
        $storage->expects($this->never())->method('insert');
        $storage->expects($this->never())->method('update');

        $this->presenter([], new Kilometer(), $storage)->updateConfiguration(
            $account,
            $this->installation($account),
            [
                'distances' => '5, invalid',
                'model' => 'unknown',
            ]
        );
    }

    public function testRejectsAnotherAccountsInstallationBeforeReadingConfiguration(): void
    {
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter([], new Kilometer(), $storage)
            ->getConfigurationData($this->account(12), $this->installation($this->account(13)));
    }

    private function presenter(
        array $configuration,
        AbstractDistanceUnit $distanceUnit,
        ?PluginConfigurationStorageInterface $storage = null
    ): PrognosePanelPresenter {
        $storage ??= $this->createStub(PluginConfigurationStorageInterface::class);
        if ($configuration !== []) {
            $storage->method('loadForPlugins')->willReturn($configuration);
        }

        $units = $this->createStub(UnitSystem::class);
        $units->method('getDistanceUnit')->willReturn($distanceUnit);

        $list = $this->createStub(RunalyzeConfigurationList::class);
        $list->method('getUnitSystem')->willReturn($units);

        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->willReturn($list);

        return new PrognosePanelPresenter(
            $this->createStub(TrainingRepository::class),
            $this->createStub(RaceresultRepository::class),
            $configurationManager,
            $storage
        );
    }

    private function account(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);

        return $account;
    }

    private function installation(Account $account, bool $inactive = false): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_Prognose');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn($inactive);
        $installation->method('getActive')->willReturn($inactive ? Plugin::STATE_INACTIVE : Plugin::STATE_ACTIVE);

        return $installation;
    }
}
