<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Service\ElevationCorrection\Strategy\Geonames;
use Runalyze\Service\ElevationCorrection\Strategy\GeoTiff;
use Runalyze\Service\ElevationCorrection\Strategy\GoogleMaps;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyCollection;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class ElevationCorrection
{
    protected StrategyCollection $StrategyCollection;

    protected ?StrategyInterface $LastSuccessfulStrategy = null;

    public function __construct(
        GeoTiff $geoTiff,
        Geonames $geonames,
        GoogleMaps $googleMaps
    ) {
        $this->StrategyCollection = new StrategyCollection();
        $this->StrategyCollection->add($geoTiff);
        $this->StrategyCollection->add($geonames);
        $this->StrategyCollection->add($googleMaps);
    }

    /**
     * @param float[] $latitudes
     * @param float[] $longitudes
     *
     * @return int[]|null altitude [m]
     */
    public function loadAltitudeData(array $latitudes, array $longitudes, ?StrategyInterface $strategy = null)
    {
        if ($strategy instanceof \Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface) {
            $this->LastSuccessfulStrategy = $strategy;

            return $strategy->loadAltitudeData($latitudes, $longitudes);
        }

        $this->LastSuccessfulStrategy = null;

        return $this->StrategyCollection->loadAltitudeData($latitudes, $longitudes);
    }

    public function getLastSuccessfulStrategy(): ?StrategyInterface
    {
        if ($this->LastSuccessfulStrategy instanceof \Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface) {
            return $this->LastSuccessfulStrategy;
        }

        return $this->StrategyCollection->getLastSuccessfulStrategy();
    }

    /**
     * @return array<string, string> strategy class name => 'not_possible'|'no_data'
     */
    public function getLastAttemptReasons(): array
    {
        return $this->StrategyCollection->getLastAttemptReasons();
    }

    /**
     * @return array<string, class-string<\Throwable>> strategy class name => exception class name
     */
    public function getLastAttemptExceptions(): array
    {
        return $this->StrategyCollection->getLastAttemptExceptions();
    }
}
