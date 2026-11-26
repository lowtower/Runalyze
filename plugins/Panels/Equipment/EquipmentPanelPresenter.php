<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Equipment;

use InvalidArgumentException;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;

final readonly class EquipmentPanelPresenter implements ModernPanelInterface, ModernPanelRequestHandlerInterface, ModernPanelMetadataInterface, ModernPanelConfigurationInterface
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_Equipment';

    private const int USAGE_BAR_MAX_WIDTH_PX = 330;

    public function __construct(
        private EquipmentRepository $equipmentRepository,
        private EquipmentTypeRepository $equipmentTypeRepository,
        private PluginConfigurationStorageInterface $configurationStorage,
        private EquipmentPanelSelectionHandler $selectionHandler,
        private ConfigurationManager $configurationManager
    ) {}

    public function getKey(): string
    {
        return self::PLUGIN_KEY;
    }

    public function getName(): string
    {
        return __('Equipment');
    }

    public function getDescription(): string
    {
        return __('Display statistics for your equipment.');
    }

    public function getConfigurationTemplate(): string
    {
        return '@Equipment/configuration.html.twig';
    }

    /**
     * @return array{
     *     equipment_types: list<array{id: int, name: string}>,
     *     selected_type_id: ?int,
     *     active: int
     * }
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation, true);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];
        $types = [];

        foreach ($this->equipmentTypeRepository->findAllForPanel($account) as $type) {
            $id = $type->getId();

            if ($id !== null) {
                $types[] = [
                    'id' => $id,
                    'name' => $type->getName(),
                ];
            }
        }

        $selectedTypeId = (int) ($configuration['type'] ?? 0);
        $validTypeIds = array_column($types, 'id');

        return [
            'equipment_types' => $types,
            'selected_type_id' => in_array($selectedTypeId, $validTypeIds, true) ? $selectedTypeId : null,
            'active' => $installation->getActive(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->selectionHandler->handle($account, $installation, $data, true);
    }

    public function getTemplate(): string
    {
        return '@Equipment/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function handleRequest(Account $account, Plugin $installation, array $queryParameters): void
    {
        $this->selectionHandler->handle($account, $installation, $queryParameters);
    }

    /**
     * @return array{
     *     equipment_types: EquipmentType[],
     *     selected_type: ?EquipmentType,
     *     equipment_rows: list<array{
     *         id: int,
     *         name: string,
     *         search_url: string,
     *         display_value: string,
     *         usage_width: int,
     *         is_active: bool
     *     }>
     * }
     *
     * @throws InvalidArgumentException if the installation does not belong to the account,
     *                                  has a different stable key, is not a panel, or is inactive
     */
    public function present(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];
        $equipmentTypes = $this->equipmentTypeRepository->findAllForPanel($account);

        $selectedTypeId = (int) ($configuration['type'] ?? 0);
        $selectedType = array_find($equipmentTypes, fn ($equipmentType): bool => $equipmentType->getId() === $selectedTypeId);

        if (! $selectedType instanceof EquipmentType) {
            return [
                'equipment_types' => $equipmentTypes,
                'selected_type' => null,
                'equipment_rows' => [],
            ];
        }

        $equipment = $this->equipmentRepository->findForPanel($selectedTypeId, $account);

        return [
            'equipment_types' => $equipmentTypes,
            'selected_type' => $selectedType,
            'equipment_rows' => $this->createEquipmentRows($equipment, $selectedType, $account),
        ];
    }

    /**
     * @throws InvalidArgumentException if the installation does not belong to the account
     */
    public static function validateInstallation(Account $account, Plugin $installation, bool $allowInactive = false): void
    {
        $accountId = $account->getId();

        if (
            $installation->getId() === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || (! $allowInactive && $installation->isInactive())
        ) {
            throw new InvalidArgumentException('The Equipment panel installation is not available for this account.');
        }
    }

    private function formatDistance(float $distance, AbstractDistanceUnit $unit): string
    {
        $isImperial = $unit->getAppendix() === 'mi';

        return number_format(
            (float) $unit->fromBaseUnit($distance),
            Distance::$DefaultDecimals,
            $isImperial ? '.' : Distance::$DecimalSeparator,
            $isImperial ? ',' : Distance::$ThousandsSeparator
        ) . ' ' . $unit->getAppendix();
    }

    /**
     * @param Equipment[] $equipment
     * @return list<array{
     *     id: int,
     *     name: string,
     *     search_url: string,
     *     display_value: string,
     *     usage_width: int,
     *     is_active: bool
     * }>
     */
    private function createEquipmentRows(array $equipment, EquipmentType $equipmentType, Account $account): array
    {
        $distanceUnit = $equipmentType->hasMaximalDistance()
            ? $this->configurationManager->getList($account)->getUnitSystem()->getDistanceUnit()
            : null;
        $showDistance = $distanceUnit instanceof AbstractDistanceUnit;
        $maxDuration = 0;

        foreach ($equipment as $item) {
            if ($maxDuration === 0) {
                $maxDuration = $item->getTime();
            }
        }

        $maxValue = match (true) {
            $showDistance => (float) $equipmentType->getMaxKm(),
            $equipmentType->hasMaximalDuration() => (float) $equipmentType->getMaxTime(),
            default => (float) max(1, $maxDuration),
        };

        $rows = [];

        foreach ($equipment as $item) {
            $value = $showDistance ? $item->getTotalDistance() : $item->getTime();
            $percentage = max(0.0, min(1.0, $value / $maxValue));
            $id = $item->getId();

            $rows[] = [
                'id' => (int) $id,
                'name' => $item->getName(),
                'search_url' => 'my/search?get=true&equipmentid=' . (int) $id,
                'display_value' => $distanceUnit instanceof AbstractDistanceUnit
                    ? $this->formatDistance($item->getTotalDistance(), $distanceUnit)
                    : new Duration($item->getTime())->string(),
                'usage_width' => (int) round($percentage * self::USAGE_BAR_MAX_WIDTH_PX),
                'is_active' => $item->isActive(),
            ];
        }

        return $rows;
    }
}
