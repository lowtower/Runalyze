<?php

namespace Runalyze\Bundle\CoreBundle\Entity\Adapter;

use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ElevationCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Parameter\Application\ElevationMethod;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class RouteAdapter
{
    /**
     * @var Route
     */
    protected $Route;

    public function __construct(Route $route)
    {
        $this->Route = $route;
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

    public function removeElevation()
    {
        $this->Route->setElevationsCorrected();
        $this->Route->setElevationsSource(null);
    }

    /**
     * @param int $threshold [m]
     */
    public function calculateElevation(ElevationMethod $method, $threshold)
    {
        $calculator = new ElevationCalculator();
        $calculator->calculateFor($this->Route, $method, $threshold);
    }

    /**
     * @param object|null $object
     *
     * @return string|null
     */
    protected function getStrategyName($object)
    {
        if ($object === null) {
            return null;
        }

        $fullClassName = get_class($object);

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }
}
