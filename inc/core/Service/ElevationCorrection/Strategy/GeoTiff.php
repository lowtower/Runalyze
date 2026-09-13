<?php

namespace Runalyze\Service\ElevationCorrection\Strategy;

use Runalyze\DEM\Reader;

class GeoTiff extends AbstractStrategy implements StrategyInterface
{
    use GuessUnknownValuesTrait;

    protected bool $UseSmoothing = true;

    public function __construct(
        protected Reader $Reader
    ) {
    }

    public function setUseSmoothing(bool $flag)
    {
        $this->UseSmoothing = $flag;
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function loadAltitudeData(array $latitudes, array $longitudes): ?array
    {
        if ($this->canHandleData($latitudes, $longitudes)) {
            $altitudes = $this->Reader->getElevations($latitudes, $longitudes);

            $this->guessUnknown($altitudes, false);
            $this->smoothAltitudes($altitudes);

            return $altitudes;
        }

        return null;
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     */
    public function canHandleData(array $latitudes, array $longitudes): bool
    {
        return $this->Reader->hasDataFor($this->getBoundsFor($latitudes, $longitudes));
    }

    /**
     * @param  float[] $latitudes
     * @param  float[] $longitudes
     *
     * @return array   array(array($lat, $lng), ...)
     */
    protected function getBoundsFor(array $latitudes, array $longitudes): array
    {
        $filteredLatitudes = array_filter($latitudes);
        $filteredLongitudes = array_filter($longitudes);

        if ($filteredLatitudes === [] || $filteredLongitudes === []) {
            return [];
        }

        $minLatitude = min($filteredLatitudes);
        $maxLatitude = max($filteredLatitudes);
        $minLongitude = min($filteredLongitudes);
        $maxLongitude = max($filteredLongitudes);

        return [
            [$minLatitude, $minLongitude],
            [$minLatitude, $maxLongitude],
            [$maxLatitude, $minLongitude],
            [$maxLatitude, $maxLongitude],
        ];
    }

    /**
     * Smooth elevation
     *
     * Although this could be more exact, a smoothing has to be used.
     * Otherwise this corrector would result in much higher cumulative elevations.
     *
     * @param int[] $altitudes [m]
     */
    protected function smoothAltitudes(array &$altitudes)
    {
        if ($altitudes === [] || ! $this->UseSmoothing) {
            return;
        }

        $arraySize = count($altitudes);
        $currentValue = $altitudes[0];

        for ($i = 0; $i < $arraySize; $i++) {
            if ($i % $this->PointsToGroup === 0) {
                $currentValue = $altitudes[$i];
            } else {
                $altitudes[$i] = $currentValue;
            }
        }
    }
}
