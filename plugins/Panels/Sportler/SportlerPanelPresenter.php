<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Sportler;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;

final readonly class SportlerPanelPresenter implements PanelInterface, PanelConfigurationInterface
{
    public function __construct(
        private SportlerPanelDataProvider $dataProvider
    ) {}

    public function getKey(): string
    {
        return 'RunalyzePluginPanel_Sportler';
    }

    public function getName(): string
    {
        return __('Body values');
    }

    public function getDescription(): string
    {
        return __('Show body values: weight, resting heart rate and values like fat-, water- and muscles-percentage.');
    }

    public function getConfigurationTemplate(): string
    {
        return '@Sportler/configuration.html.twig';
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        return $this->dataProvider->getConfigurationData($account, $installation);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->dataProvider->updateConfiguration($account, $installation, $data);
    }

    public function getTemplate(): string
    {
        return '@Sportler/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return false;
    }

    /**
     * @return array{
     *     pluginId: int,
     *     isHidden: bool,
     *     configuration: array{desiredWeight: int, plotPoints: int, plotTimerange: int},
     *     latest: array{weight: ?float, pulseRest: ?int, pulseMax: ?int, fat: ?float, water: ?float, muscles: ?float},
     *     weightHistory: list<array{time: int, weight: ?float, pulseRest: ?int}>,
     *     bodyCompositionHistory: list<array{time: int, fat: ?float, water: ?float, muscles: ?float}>
     * }
     */
    public function present(Account $account, Plugin $installation): array
    {
        $data = $this->dataProvider->getData($account, $installation);

        if (count($data['weightHistory']) === 1) {
            $data['weightHistory'][] = $data['weightHistory'][0];
        }

        if (count($data['bodyCompositionHistory']) === 1) {
            $data['bodyCompositionHistory'][] = $data['bodyCompositionHistory'][0];
        }

        return $data;
    }
}
