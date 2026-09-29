<?php

declare(strict_types=1);

/**
 * This file contains class::Adjustment
 * @package Runalyze\Calculation\JD\Correction
 */

namespace Runalyze\Calculation\Elevation;

use Runalyze\Configuration;

/**
 * Modifier
 *
 * Example: elevation +100/-50m
 * Correction: positive +2, negative -1
 * Result: adds 2*100 -1*50 = 150m to the distance
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Elevation
 */
class DistanceModifier
{
    /**
     * Distance to add for 1m positive elevation
     */
    protected int $PositiveCorrector = 0;

    /**
     * Distance to add for 1m negative elevation
     */
    protected int $NegativeCorrector = 0;

    /**
     * Distance
     */
    protected float $Distance;

    /**
     * Elevation up
     */
    protected int $Up;

    /**
     * Elevation down
     */
    protected int $Down;

    /**
     * Construct
     *
     * @param ?float $distance [optional] [km]
     * @param ?int $up [optional] [m]
     * @param ?int $down [optional] [m]
     * @param \Runalyze\Configuration\Category\VO2max $config [optional]
     */
    public function __construct(?float $distance = 0, ?int $up = 0, ?int $down = 0, ?Configuration\Category\VO2max $config = null)
    {
        if ($config instanceof \Runalyze\Configuration\Category\VO2max) {
            $this->setCorrectionValues($config->correctionForPositiveElevation(), $config->correctionForNegativeElevation());
        }

        $this->setDistance($distance);
        $this->setElevation($up, $down);
    }

    /**
     * Distance
     * @param ?float $distance [km]
     */
    public function setDistance(?float $distance): void
    {
        $this->Distance = (float) $distance;
    }

    /**
     * @param ?int $up [m]
     * @param ?int $down [m]
     * (int) type casting results in 0 elevation up or down
     */
    public function setElevation(?int $up, ?int $down): void
    {
        $this->Up = (int) $up;
        $this->Down = (int) $down;
    }

    /**
     * @param int $positiveCorrection [m]
     * @param int $negativeCorrection [m]
     */
    public function setCorrectionValues(int $positiveCorrection, int $negativeCorrection): void
    {
        $this->PositiveCorrector = $positiveCorrection;
        $this->NegativeCorrector = $negativeCorrection;
    }

    /**
     * @return float [km]
     */
    public function additionalDistance(): float
    {
        return ($this->PositiveCorrector * $this->Up + $this->NegativeCorrector * $this->Down) / 1000;
    }

    /**
     * @return float [km]
     */
    public function correctedDistance(): float
    {
        return $this->Distance + $this->additionalDistance();
    }
}
