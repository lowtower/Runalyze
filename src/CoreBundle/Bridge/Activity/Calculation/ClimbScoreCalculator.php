<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\Climb;
use Runalyze\Sports\ClimbQuantification\FietsIndex;
use Runalyze\Sports\ClimbScore\ClimbScore;

class ClimbScoreCalculator
{
    /**
     * @var float [m/m]
     */
    public const float CLIMB_GRADIENT_THRESHOLD = 0.02;

    /**
     * @var float [km]
     */
    public const float CLIMB_DISTANCE_THRESHOLD = 0.1;

    public function __construct(
        private readonly FietsIndex $fietsIndex,
        private readonly ClimbFinder $climbFinder
    ) {
    }

    public function calculateFor(Training $activity): ClimbScore
    {
        $score = new ClimbScore();

        if ($activity->hasRoute() && $activity->hasTrackdata()) {
            $score->setScoreFromClassifiedClimbs(
                $this->getFietsIndicesFor($activity),
                $activity->getRoute()
                    ->getDistance(),
                $activity->getPercentageFlat()
            );
        }

        $activity->setClimbScore($score->getScore());

        return $score;
    }

    protected function getFietsIndicesFor(Training $activity): array
    {
        $climbs = $this->climbFinder->findClimbsFor($activity);

        return array_map(function (Climb $climb): float {
            if ($climb->getGradient() < self::CLIMB_GRADIENT_THRESHOLD || $climb->getDistance() <= self::CLIMB_DISTANCE_THRESHOLD) {
                return 0.0;
            }

            if ($climb->knowsClimbProfile()) {
                return $this->fietsIndex->getScoreForProfile($climb->getClimbProfile()->getDistancesWithGradients(), $climb->getAltitudeAtTop());
            }

            return $this->fietsIndex->getScoreFor($climb->getDistance(), $climb->getElevation(), (int) $climb->getAltitudeAtTop());
        }, $climbs->getElements());
    }
}
