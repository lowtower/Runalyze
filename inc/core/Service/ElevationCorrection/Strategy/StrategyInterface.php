<?php

declare(strict_types=1);

namespace Runalyze\Service\ElevationCorrection\Strategy;

interface StrategyInterface
{
    public function isPossible(): bool;

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     *
     * @return int[]|null altitude [m]
     */
    public function loadAltitudeData(array $latitudes, array $longitudes): ?array;
}
