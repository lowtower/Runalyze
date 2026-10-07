<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\Climb;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbCollection;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile;
use Runalyze\Mathematics\PointReduction\RamerDouglasPeucker;

class ClimbFinder
{
    /**
     * @var float [-]
     */
    public const EPSILON_DEFAULT = 1.1;

    /**
     * @var float [m]
     */
    public const EPSILON_FOR_CLIMB_PROFILE = 0.1;

    public function findClimbsFor(Training $activity, float $epsilon = self::EPSILON_DEFAULT): ClimbCollection
    {
        if ($activity->hasRoute() && $activity->getRoute()->hasElevations() && $activity->hasTrackdata() && $activity->getTrackdata()->getDistance() !== null) {
            $distances = $activity->getTrackdata()
                ->getDistance();
            $elevations = $activity->getRoute()
                ->getElevations();
            $cuttingIndices = new RamerDouglasPeucker($distances, $elevations, $epsilon)->getReducedIndices();

            return $this->createClimbsForIndices($cuttingIndices, $distances, $elevations);
        }

        return new ClimbCollection();
    }

    /**
     * @param array<int, mixed> $indices
     */
    protected function createClimbsForIndices(array $indices, array $distances, array $elevations): ClimbCollection
    {
        $numIndices = count($indices);
        $climbs = new ClimbCollection();

        for ($i = 0; $i < $numIndices - 1; ++$i) {
            $index = $indices[$i];
            $endIndex = $indices[$i + 1];

            if ($elevations[$endIndex] > $elevations[$index] && $distances[$endIndex] > $distances[$index]) {
                $climb = new Climb(
                    $distances[$endIndex] - $distances[$index],
                    $elevations[$endIndex] - $elevations[$index],
                    $index,
                    $endIndex
                );
                $climb->setAltitudeAtTop($elevations[$endIndex]);
                $climb->setClimbProfile($this->getClimbProfileFor(
                    array_slice($distances, $index, $endIndex - $index + 1),
                    array_slice($elevations, $index, $endIndex - $index + 1)
                ));

                $climbs->add($climb);
            }
        }

        return $climbs;
    }

    protected function getClimbProfileFor(array $distances, array $elevations): ClimbProfile
    {
        return ClimbProfile::getClimbProfileFor($distances, $elevations, self::EPSILON_FOR_CLIMB_PROFILE);
    }
}
