<?php

declare(strict_types=1);

namespace Runalyze\Sports\Running\GradeAdjustedPace\Algorithm;

use Runalyze\Sports\Running\GradeAdjustedPace\AlgorithmInterface;

abstract class AbstractEnergyCostAlgorithm implements AlgorithmInterface
{
    /**
     * @var float
     */
    public const TIME_ADJUSTMENT_EXPONENT = 0.83;

    abstract public function getEnergyCost(float $gradientInPercent);

    public function getTimeFactor(float $gradientInPercent): float
    {
        $energyCost = $this->getEnergyCost($gradientInPercent);

        if ($energyCost <= 0.0) {
            return 0.0;
        }

        return (1.0 / $energyCost) ** self::TIME_ADJUSTMENT_EXPONENT;
    }

    public function adjustPace(float $paceInSecondsPerKilometer, float $gradientInPercent): float
    {
        return $paceInSecondsPerKilometer * $this->getTimeFactor($gradientInPercent);
    }
}
