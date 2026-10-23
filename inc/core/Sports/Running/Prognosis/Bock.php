<?php

namespace Runalyze\Sports\Running\Prognosis;

/**
 * Prognosis by Robert Bock
 *
 * Competition prediction based on CPP method by Robert Bock.
 * CPP stands for 'Competitive Performance Predictor'.
 * This method does not require the additional basic endurance calculation.
 *
 * @see http://www.robert-bock.de/Sport_0/lauf_7/cpp/cpp.html
 */
class Bock implements PrognosisInterface
{
    public const float MINIMAL_DISTANCE_FOR_REFERENCE_RESULT = 3.0;

    public const float K_LOWER_BOUND = 132.0;

    public const float K_UPPER_BOUND = 1800.0;

    public const float E_LOWER_BOUND = 1.0;

    public const float E_UPPER_BOUND = 2.0;

    protected float $K = 0.0;

    protected float $E = 1.0;

    /**
     * @param float $distance1 [km]
     * @param float $time1 [s]
     * @param float $distance2 [km]
     * @param float $time2 [s]
     */
    public function __construct(float $distance1 = 0.0, float $time1 = 0.0, float $distance2 = 0.0, float $time2 = 0.0)
    {
        $this->setFromResults($distance1, $time1, $distance2, $time2);
    }

    /**
     * Set constants K and e from given results (should not be too similar in distance).
     * The documented version does not work. Log-version is from source-code.
     *
     * @see http://www.robert-bock.de/Sport_0/lauf_7/cpp/cpp.html
     * @see http://www.robert-bock.de/Sonstiges/cpp2.htm
     *
     * @param float $distance1 [km]
     * @param float $time1 [s]
     * @param float $distance2 [km]
     * @param float $time2 [s]
     */
    public function setFromResults(float $distance1, float $time1, float $distance2, float $time2): self
    {
        if ($distance1 <= 0.0 || $distance2 <= 0.0) {
            return $this;
        }

        if ($distance1 > $distance2) {
            [$distance1, $time1, $distance2, $time2] = [$distance2, $time2, $distance1, $time1];
        }

        $this->E = log($time2 / $time1) / log($distance2 / $distance1);
        $this->K = $time2 / $distance2 ** $this->E;

        return $this;
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1258
     */
    public function areValuesValid(): bool
    {
        return
            ($this->K > self::K_LOWER_BOUND) &&
            ($this->K < self::K_UPPER_BOUND) &&
            ($this->E > self::E_LOWER_BOUND) &&
            ($this->E < self::E_UPPER_BOUND)
        ;
    }

    public function getSeconds(float $distance): float
    {
        $seconds = $this->K * $distance ** $this->E;

        return ($distance > 3.0) ? round($seconds) : $seconds;
    }

    public function getK(): float
    {
        return $this->K;
    }

    public function getE(): float
    {
        return $this->E;
    }
}
