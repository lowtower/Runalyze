<?php

namespace Runalyze\Bundle\CoreBundle\Entity\Adapter;

use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ElevationCalculator;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Parameter\Application\ElevationMethod;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class RouteAdapter
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\Route $Route
    ) {
    }

    /**
     * @return bool true on success
     */
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
    public function calculateElevation(ElevationMethod $method, $threshold): void
    {
        $calculator = new ElevationCalculator();
        $calculator->calculateFor($this->Route, $method, $threshold);
    }

    /**
     * @param object|null $object
     */
    protected function getStrategyName($object): ?string
    {
        if ($object === null) {
            return null;
        }

        $fullClassName = $object::class;

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }
}
