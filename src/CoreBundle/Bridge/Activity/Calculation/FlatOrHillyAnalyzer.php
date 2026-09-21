<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile;

class FlatOrHillyAnalyzer
{
    /**
     * @var float
     */
    public const DEFAULT_GRADIENT_THRESHOLD = 0.02;

    /**
     * @param float $threshold
     */
    public function calculatePercentageHillyFor(Training $activity, $threshold = self::DEFAULT_GRADIENT_THRESHOLD): ?float
    {
        $percentageFlat = null;

        if ($activity->hasRoute() && $activity->getRoute()->hasElevations() && $activity->hasTrackdata() && $activity->getTrackdata()->getDistance() !== null) {
            $percentageFlat = $this->calculatePercentageFlatForArrays(
                $activity->getTrackdata()
                    ->getDistance(),
                $activity->getRoute()
                    ->getElevations(),
                $threshold
            );
        }

        $activity->setPercentageHilly($percentageFlat !== null ? 1.0 - $percentageFlat : null);

        return $percentageFlat;
    }

    /**
     * @param float[] $distances
     * @param int[] $elevations
     * @param float $threshold
     */
    public function calculatePercentageFlatForArrays(array $distances, array $elevations, $threshold = self::DEFAULT_GRADIENT_THRESHOLD): ?float
    {
        if ($distances === []) {
            return null;
        }

        $distanceFlat = 0.0;
        $distanceHilly = 0.0;
        $distancesWithGradients = ClimbProfile::getClimbProfileFor($distances, $elevations)->getDistancesWithGradients();

        foreach ($distancesWithGradients as $data) {
            if ($data[1] >= $threshold || $data[1] < -$threshold) {
                $distanceHilly += $data[0];
            } else {
                $distanceFlat += $data[0];
            }
        }

        return $distanceFlat === 0.0 ? 0.0 : $distanceFlat / ($distanceFlat + $distanceHilly);
    }
}
