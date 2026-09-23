<?php

declare(strict_types=1);

namespace Runalyze\Sports\Running\VO2max\Estimation;

/**
 * @see J. Daniels, The Conditioning for Distance Running--the Scientific Aspects, John Wiley & Sons, New York, 1978
 * @see T. Noakes, Lore of Running (4th edition), Oxford University Press Southern Africa, 2001
 * @see http://www.simpsonassociatesinc.com/runningmath3.htm
 * @see http://www.had2know.com/health/vo2-max-calculator-racing-daniels-gilbert.html
 */
class DanielsGilbertFormula implements EstimationInterface
{
    public function estimateFromRaceResult(?float $distance, float $seconds): float
    {
        $min = (int) $seconds / 60;
        $m = 1000 * $distance;

        if (! $distance || $m <= 0.0 || $min <= 0.0 || $m / $min < 50.0 || $m / $min > 1000.0) {
            return 0.0;
        }

        return $this->estimateFromVelocity($m / $min) / $this->evaluateDropDead($min);
    }

    public function estimateFromVelocity(float $meterPerMinute): float
    {
        return max(0.0, -4.6 + 0.182253 * $meterPerMinute + 0.000104 * $meterPerMinute * $meterPerMinute);
    }

    /**
     * Formerly known as 'drop dead'
     *
     * @param float $minutes [min]
     */
    public function evaluateDropDead(float $minutes): float
    {
        return 0.8 + 0.1894393 * exp(-0.012778 * $minutes) + 0.2989558 * exp(-0.1932605 * $minutes);
    }
}
