<?php

declare(strict_types=1);

namespace Runalyze\Plugins\TagsSummary;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;

final readonly class TagsSummaryPanelSelectionHandler
{
    public function __construct(
        private SportRepository $sportRepository,
        private PluginConfigurationStorageInterface $configurationStorage
    ) {}

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function handle(Account $account, Plugin $installation, array $queryParameters): void
    {
        TagsSummaryPanelPresenter::validateInstallation($account, $installation);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $sports = $this->sportRepository->findAllFor($account);
        $validSportIds = array_map(static fn ($sport): ?int => $sport->getId(), $sports);

        foreach (['sport', 'tagssummary_timescale'] as $key) {
            $requested = $queryParameters[$key] ?? null;

            if (! is_string($requested) || ! ctype_digit($requested)) {
                continue;
            }

            $value = (int) $requested;

            if ($key === 'sport' && ! in_array($value, $validSportIds, true)) {
                continue;
            }

            if ($key === 'tagssummary_timescale' && ! in_array($value, TagsSummaryPanelPresenter::TIMESCALES, true)) {
                continue;
            }

            $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

            if (array_key_exists($key, $configuration)) {
                $this->configurationStorage->update($pluginId, $key, (string) $value);
            } else {
                $this->configurationStorage->insert($pluginId, $key, (string) $value);
            }
        }
    }
}
