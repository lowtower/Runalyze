<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Equipment;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;

final readonly class EquipmentPanelSelectionHandler
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_Equipment';

    public function __construct(
        private EquipmentTypeRepository $equipmentTypeRepository,
        private PluginConfigurationStorageInterface $configurationStorage
    ) {}

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws InvalidArgumentException if the installation does not belong to the account,
     *                                  has a different stable key, is not a panel, or is inactive
     */
    public function handle(Account $account, Plugin $installation, array $queryParameters): void
    {
        $pluginId = $installation->getId();
        $accountId = $account->getId();

        if (
            $pluginId === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || $installation->isInactive()
        ) {
            throw new InvalidArgumentException('The Equipment panel installation is not available for this account.');
        }

        $requestedType = $queryParameters['type'] ?? null;

        if (! is_string($requestedType) || ! ctype_digit($requestedType)) {
            return;
        }

        $requestedTypeId = (int) $requestedType;

        foreach ($this->equipmentTypeRepository->findAllForPanel($account) as $equipmentType) {
            if ($equipmentType->getId() !== $requestedTypeId) {
                continue;
            }

            $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

            if (array_key_exists('type', $configuration)) {
                $this->configurationStorage->update($pluginId, 'type', (string) $requestedTypeId);
            } else {
                $this->configurationStorage->insert($pluginId, 'type', (string) $requestedTypeId);
            }

            return;
        }
    }
}
