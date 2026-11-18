<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Sportler;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;

final readonly class SportlerPanelPresenter implements ModernPanelInterface
{
    public function __construct(
        private SportlerPanelDataProvider $dataProvider
    ) {}

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
    public function getKey(): string
    {
        return 'RunalyzePluginPanel_Sportler';
    }

    public function getTemplate(): string
    {
        return '@Sportler/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return false;
    }

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
