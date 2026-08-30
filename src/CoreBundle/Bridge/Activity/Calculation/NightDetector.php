<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Util\LocalTime;

class NightDetector
{
    public function isActivityAtNight(Training $activity): ?bool
    {
        if (! $activity->hasRoute() || ! $activity->getRoute()->hasGeohashes()) {
            return null;
        }

        // TODO use activity's offset if known
        /** @var Geohash $geohashObj */
        $geohashObj = (new Geohash())->decode($activity->getRoute()->getStartpoint());
        $detector = new \Runalyze\Calculation\NightDetector();
        $detector->setFrom(
            (int) ((new LocalTime($activity->getTime()))
                ->toServerTimestamp() + 0.5 * $activity->getS()),
            $geohashObj->getCoordinate()
        );

        return $detector->isNight();
    }
}
