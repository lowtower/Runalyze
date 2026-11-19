<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Equipment\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Metrics\Distance\Unit\Kilometer;
use Runalyze\Metrics\Distance\Unit\Miles;
use Runalyze\Plugins\Equipment\EquipmentPanelPresenter;
use Runalyze\Plugins\Equipment\EquipmentPanelSelectionHandler;

#[Group('plugin')]
final class EquipmentPanelPresenterTest extends TestCase
{
    public function testPresentsEquipmentWithDistanceUsageAndSearchLinks(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($account);
        $equipmentType = $this->createEquipmentType(3, true, 500);
        $equipment = $this->createEquipment(9, 'Running shoes', 250, 3600, true);

        $configurationStorage = $this->createMock(PluginConfigurationStorageInterface::class);
        $configurationStorage->expects($this->once())
            ->method('loadForPlugins')
            ->with([5])
            ->willReturn([
                5 => [
                    'type' => '3',
                ],
            ]);

        $equipmentTypeRepository = $this->createMock(EquipmentTypeRepository::class);
        $equipmentTypeRepository->expects($this->once())
            ->method('findAllForPanel')
            ->with($account)
            ->willReturn([$equipmentType]);

        $equipmentRepository = $this->createMock(EquipmentRepository::class);
        $equipmentRepository->expects($this->once())
            ->method('findForPanel')
            ->with(3, $account)
            ->willReturn([$equipment]);

        $presenter = new EquipmentPanelPresenter(
            $equipmentRepository,
            $equipmentTypeRepository,
            $configurationStorage,
            new EquipmentPanelSelectionHandler($equipmentTypeRepository, $configurationStorage),
            $this->createConfigurationManager(new Kilometer())
        );

        $data = $presenter->present($account, $installation);

        $this->assertSame($equipmentType, $data['selected_type']);
        $this->assertCount(1, $data['equipment_rows']);
        $this->assertSame(9, $data['equipment_rows'][0]['id']);
        $this->assertSame('Running shoes', $data['equipment_rows'][0]['name']);
        $this->assertSame('my/search?get=true&equipmentid=9', $data['equipment_rows'][0]['search_url']);
        $this->assertSame(165, $data['equipment_rows'][0]['usage_width']);
        $this->assertTrue($data['equipment_rows'][0]['is_active']);
        $this->assertSame('250,000 km', $data['equipment_rows'][0]['display_value']);
    }

    public function testFormatsDistanceUsingTheAccountImperialUnitSystem(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($account);
        $equipmentType = $this->createEquipmentType(3, true, 500);
        $equipment = $this->createEquipment(9, 'Running shoes', 250, 3600, true);

        $presenter = $this->createPresenter(
            [$equipmentType],
            [$equipment],
            [
                5 => [
                    'type' => '3',
                ],
            ],
            new Miles()
        );

        $data = $presenter->present($account, $installation);

        $this->assertSame('155.343 mi', $data['equipment_rows'][0]['display_value']);
    }

    public function testUsesTheConfiguredDurationLimitForUsage(): void
    {
        $account = $this->createAccount(12);
        $equipmentType = $this->createEquipmentType(3, false, null, true, 7200);
        $equipment = $this->createEquipment(9, 'Running shoes', 0, 3600, true);

        $installation = $this->createInstallation($account);
        $presenter = $this->createPresenter(
            [$equipmentType],
            [$equipment],
            [
                5 => [
                    'type' => '3',
                ],
            ]
        );

        $data = $presenter->present($account, $installation);

        $this->assertSame(165, $data['equipment_rows'][0]['usage_width']);
    }

    public function testUsesTheFirstNonZeroEquipmentDurationAsFallbackUsageLimit(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($account);
        $equipmentType = $this->createEquipmentType(3, false, null);
        $equipment = [
            $this->createEquipment(9, 'Unused shoes', 0, 0, true),
            $this->createEquipment(10, 'Current shoes', 0, 3600, true),
            $this->createEquipment(11, 'Older shoes', 0, 1800, false),
        ];

        $presenter = $this->createPresenter(
            [$equipmentType],
            $equipment,
            [
                5 => [
                    'type' => '3',
                ],
            ]
        );

        $data = $presenter->present($account, $installation);

        $this->assertSame([0, 330, 165], array_column($data['equipment_rows'], 'usage_width'));
    }

    public function testPersistsAValidTypeSelectedFromThePanelMenu(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($account);
        $equipmentType = $this->createEquipmentType(3, true, 500);

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->once())
            ->method('loadForPlugins')
            ->with([5])
            ->willReturn([]);
        $storage->expects($this->once())
            ->method('insert')
            ->with(5, 'type', '3');

        $typeRepository = $this->createMock(EquipmentTypeRepository::class);
        $typeRepository->expects($this->once())
            ->method('findAllForPanel')
            ->with($account)
            ->willReturn([$equipmentType]);

        $handler = new EquipmentPanelSelectionHandler($typeRepository, $storage);

        $handler->handle($account, $installation, [
            'type' => '3',
        ]);
    }

    public function testDoesNotPersistAnUnknownTypeSelection(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($account);
        $equipmentType = $this->createEquipmentType(3, true, 500);

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');
        $storage->expects($this->never())->method('insert');
        $storage->expects($this->never())->method('update');

        $typeRepository = $this->createMock(EquipmentTypeRepository::class);
        $typeRepository->expects($this->once())
            ->method('findAllForPanel')
            ->with($account)
            ->willReturn([$equipmentType]);

        $handler = new EquipmentPanelSelectionHandler($typeRepository, $storage);

        $handler->handle($account, $installation, [
            'type' => '999',
        ]);
    }

    public function testSelectionHandlerRejectsAnInstallationOwnedByAnotherAccount(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($this->createAccount(13));

        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');

        $typeRepository = $this->createMock(EquipmentTypeRepository::class);
        $typeRepository->expects($this->never())->method('findAllForPanel');

        $handler = new EquipmentPanelSelectionHandler($typeRepository, $storage);

        $this->expectException(InvalidArgumentException::class);
        $handler->handle($account, $installation, [
            'type' => '3',
        ]);
    }

    public function testRejectsAnInstallationOwnedByAnotherAccount(): void
    {
        $account = $this->createAccount(12);
        $installation = $this->createInstallation($this->createAccount(13));

        $presenter = $this->createPresenter([], [], []);

        $this->expectException(InvalidArgumentException::class);
        $presenter->present($account, $installation);
    }

    public function testDeclaresItsStableKeyTemplateAndTrainingReloadBehavior(): void
    {
        $presenter = $this->createPresenter([], [], []);

        $this->assertSame('RunalyzePluginPanel_Equipment', $presenter->getKey());
        $this->assertSame('@Equipment/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    /**
     * @param EquipmentType[] $equipmentTypes
     * @param Equipment[] $equipment
     * @param array<int, array<string, string>> $configuration
     */
    private function createPresenter(
        array $equipmentTypes,
        array $equipment,
        array $configuration,
        ?AbstractDistanceUnit $distanceUnit = null
    ): EquipmentPanelPresenter {
        $storage = $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn($configuration);

        $typeRepository = $this->createStub(EquipmentTypeRepository::class);
        $typeRepository->method('findAllForPanel')->willReturn($equipmentTypes);

        $equipmentRepository = $this->createStub(EquipmentRepository::class);
        $equipmentRepository->method('findForPanel')->willReturn($equipment);

        return new EquipmentPanelPresenter(
            $equipmentRepository,
            $typeRepository,
            $storage,
            new EquipmentPanelSelectionHandler($typeRepository, $storage),
            $this->createConfigurationManager($distanceUnit ?? new Kilometer())
        );
    }

    private function createConfigurationManager(AbstractDistanceUnit $distanceUnit): ConfigurationManager
    {
        $unitSystem = $this->createStub(UnitSystem::class);
        $unitSystem->method('getDistanceUnit')->willReturn($distanceUnit);

        $configurationList = $this->createStub(RunalyzeConfigurationList::class);
        $configurationList->method('getUnitSystem')->willReturn($unitSystem);

        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->willReturn($configurationList);

        return $configurationManager;
    }

    private function createAccount(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);

        return $account;
    }

    private function createInstallation(Account $account): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_Equipment');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn(false);

        return $installation;
    }

    private function createEquipmentType(
        int $id,
        bool $hasMaximalDistance,
        ?int $maxKm,
        bool $hasMaximalDuration = false,
        ?int $maxTime = null
    ): EquipmentType {
        $equipmentType = $this->createStub(EquipmentType::class);
        $equipmentType->method('getId')->willReturn($id);
        $equipmentType->method('hasMaximalDistance')->willReturn($hasMaximalDistance);
        $equipmentType->method('getMaxKm')->willReturn($maxKm);
        $equipmentType->method('hasMaximalDuration')->willReturn($hasMaximalDuration);
        $equipmentType->method('getMaxTime')->willReturn($maxTime);

        return $equipmentType;
    }

    private function createEquipment(
        int $id,
        string $name,
        float $distance,
        int $time,
        bool $isActive
    ): Equipment {
        $equipment = $this->createStub(Equipment::class);
        $equipment->method('getId')->willReturn($id);
        $equipment->method('getName')->willReturn($name);
        $equipment->method('getTotalDistance')->willReturn($distance);
        $equipment->method('getTime')->willReturn($time);
        $equipment->method('isActive')->willReturn($isActive);

        return $equipment;
    }
}
