<?php

declare(strict_types=1);

namespace Runalyze\Sports\Running\Prognosis;

interface PrognosisInterface
{
    /**
     * @param float $distance [km]
     * @return float|null prognosis for given distance [s], null if not possible
     */
    public function getSeconds(float $distance): ?float;

    public function areValuesValid(): bool;
}
