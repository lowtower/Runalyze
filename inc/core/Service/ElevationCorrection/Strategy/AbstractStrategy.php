<?php

declare(strict_types=1);

namespace Runalyze\Service\ElevationCorrection\Strategy;

abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * Points to group together
     *
     * This is only a bad guess.
     * It would be better to decide this by distance between the points.
     */
    protected int $PointsToGroup = 5;

    public function setPointsToGroup(int $numberOfPoints)
    {
        $this->PointsToGroup = $numberOfPoints;
    }
}
