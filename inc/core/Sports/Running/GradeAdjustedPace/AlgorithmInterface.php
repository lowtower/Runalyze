<?php

declare(strict_types=1);

namespace Runalyze\Sports\Running\GradeAdjustedPace;

interface AlgorithmInterface
{
    /**
     * @param float $gradientInPercent gradient in percent [-1.00 1.00]
     * @return float relative time factor to adjust the time spent running a given distance with that gradient
     */
    public function getTimeFactor(float $gradientInPercent): float;

    /**
     * @param float $paceInSecondsPerKilometer [s/km]
     * @param float $gradientInPercent gradient in percent [-1.00 1.00]
     * @return float adjusted pace [s/km]
     */
    public function adjustPace(float $paceInSecondsPerKilometer, float $gradientInPercent): float;
}
