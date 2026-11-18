<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Sportler;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;

final readonly class SportlerPanelPresenter
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
