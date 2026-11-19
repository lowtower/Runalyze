<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Sportler;

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
     *     configuration: array{desiredWeight: int, plotPoints: int, plotTimerange: int},
     *     active: int
     * }
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function getConfigurationData(Account $account, PluginInstallation $installation): array
    {
        self::validateInstallation($account, $installation, true);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $stored = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        return [
            'configuration' => [
                'desiredWeight' => (int) ($stored['wunschgewicht'] ?? 0),
                'plotPoints' => (int) ($stored['plot_points'] ?? 20),
                'plotTimerange' => (int) ($stored['plot_timerange'] ?? 0),
            ],
            'active' => $installation->getActive(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function updateConfiguration(Account $account, PluginInstallation $installation, array $data): void
    {
        self::validateInstallation($account, $installation, true);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $stored = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        foreach (['wunschgewicht', 'plot_points', 'plot_timerange'] as $key) {
            $raw = $data[$key] ?? null;

            if (! is_string($raw) || ! ctype_digit($raw)) {
                continue;
            }

            $value = filter_var($raw, FILTER_VALIDATE_INT);

            if ($value === false || ($key === 'plot_points' && $value < 1)) {
                continue;
            }

            if (array_key_exists($key, $stored)) {
                $this->configurationStorage->update($pluginId, $key, (string) $value);
            } else {
                $this->configurationStorage->insert($pluginId, $key, (string) $value);
            }
        }
    }

    /**
     * @throws InvalidArgumentException if the installation does not belong to the account
     */
    public static function validateInstallation(Account $account, PluginInstallation $installation, bool $allowInactive = false): void
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
            throw new InvalidArgumentException('The Sportler panel installation is not available for this account.');
        }
    }

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
        self::validateInstallation($account, $installation);

        $configuration = $this->getConfigurationData($account, $installation)['configuration'];
        $pluginId = $installation->getId();
        assert($pluginId !== null);

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
