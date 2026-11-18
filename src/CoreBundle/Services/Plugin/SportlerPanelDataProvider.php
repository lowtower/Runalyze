<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Plugin;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class SportlerPanelDataProvider
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_Sportler';

    public function __construct(
        private UserRepository $userRepository,
        private PluginConfigurationStorageInterface $configurationStorage,
        private ClockInterface $clock
    ) {}

    /**
     * @return array{
     *     pluginId: int,
     *     isHidden: bool,
     *     configuration: array{desiredWeight: int, plotPoints: int, plotTimerange: int},
     *     latest: array{weight: ?float, pulseRest: ?int, pulseMax: ?int, fat: ?float, water: ?float, muscles: ?float},
     *     weightHistory: array<array{time: int, weight: ?float, pulseRest: ?int}>,
     *     bodyCompositionHistory: array<array{time: int, fat: ?float, water: ?float, muscles: ?float}>
     * }
     *
     * @throws InvalidArgumentException if the installation does not belong to the account,
     *                                  has a different stable key, is not a panel, or is inactive
     */
    public function getData(Account $account, PluginInstallation $installation): array
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
            throw new InvalidArgumentException('The Sportler panel installation is not available for this account.');
        }

        $storedConfiguration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        $configuration = [
            'desiredWeight' => (int) ($storedConfiguration['wunschgewicht'] ?? 0),
            'plotPoints' => (int) ($storedConfiguration['plot_points'] ?? 20),
            'plotTimerange' => (int) ($storedConfiguration['plot_timerange'] ?? 0),
        ];

        $latest = $this->userRepository->getLatestEntryFor($account);

        if ($latest instanceof User) {
            $latestValues = [
                'weight' => $latest->getWeight(),
                'pulseRest' => $latest->getPulseRest(),
                'pulseMax' => $latest->getPulseMax(),
                'fat' => $latest->getFat(),
                'water' => $latest->getWater(),
                'muscles' => $latest->getMuscles(),
            ];
        } else {
            $latestValues = [
                'weight' => 0.0,
                'pulseRest' => 0,
                'pulseMax' => 0,
                'fat' => 0.0,
                'water' => 0.0,
                'muscles' => 0.0,
            ];
        }

        if ($configuration['plotTimerange'] > 0) {
            $timestamp = $this->clock->now()->getTimestamp() - ($configuration['plotTimerange'] * 86400);
            $weightHistory = array_reverse($this->userRepository->findWeightHistorySince($account, $timestamp));
            $bodyCompositionHistory = array_reverse($this->userRepository->findBodyCompositionHistorySince($account, $timestamp));
        } else {
            $weightHistory = array_reverse($this->userRepository->findWeightHistoryFor($account, $configuration['plotPoints']));
            $bodyCompositionHistory = array_reverse($this->userRepository->findBodyCompositionHistoryFor($account, $configuration['plotPoints']));
        }

        return [
            'pluginId' => $pluginId,
            'isHidden' => $installation->isHidden(),
            'configuration' => $configuration,
            'latest' => $latestValues,
            'weightHistory' => $weightHistory,
            'bodyCompositionHistory' => $bodyCompositionHistory,
        ];
    }
}
