<?php

namespace Runalyze\Bundle\CoreBundle\Entity\Adapter;

use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ElevationCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Parameter\Application\ElevationMethod;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class RouteAdapter
{
    public function __construct(
        protected Route $Route
    ) {
    }

    public function correctElevation(ElevationCorrection $elevationCorrection, ?StrategyInterface $strategy = null): bool
    {
        if ($this->Route->hasGeohashes()) {
            [$latitudes, $longitudes] = $this->Route->getLatitudesAndLongitudes();
            $altitudeData = $elevationCorrection->loadAltitudeData($latitudes, $longitudes, $strategy);

            if ($altitudeData !== null) {
                $this->Route->setElevationsCorrected($altitudeData);
                $this->Route->setElevationsSource($this->getStrategyName($elevationCorrection->getLastSuccessfulStrategy()));

                return true;
            }
        }

        return false;
    }

    public function removeElevation(): void
    {
        $this->Route->setElevationsCorrected();
        $this->Route->setElevationsSource(null);
    }

    /**
     * @param int $threshold [m]
     */
    public function calculateElevation(ElevationMethod $method, int $threshold): void
    {
        $calculator = new ElevationCalculator();
        $calculator->calculateFor($this->Route, $method, $threshold);
    }

    protected function getStrategyName(?object $object): ?string
    {
        if ($object === null) {
            return null;
        }

        $fullClassName = $object::class;

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }
}
