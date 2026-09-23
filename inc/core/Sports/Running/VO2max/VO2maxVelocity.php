<?php

namespace Runalyze\Sports\Running\VO2max;

class VO2maxVelocity
{
    /**
     * @var float [ml/kg/min]
     */
    protected float $EffectiveVO2max = 0.0;

    /**
     * @param ?float $effectiveVO2max [ml/kg/min]
     */
    public function __construct(?float $effectiveVO2max = null)
    {
        if ($effectiveVO2max !== null) {
            $this->setEffectiveVO2max($effectiveVO2max);
        }
    }

    /**
     * @param float $effectiveVO2max [ml/kg/min]
     */
    public function setEffectiveVO2max(float $effectiveVO2max): self
    {
        $this->EffectiveVO2max = $effectiveVO2max;

        return $this;
    }

    /**
     * Velocity calculated as inverse of DanielsGilbertFormula::estimateFromVelocity()
     *
     * @param ?float $effectiveVO2max [ml/kg/min]
     * @param float $percentage %vVO2max, typically in [0.5, 1.2]
     *
     * @return float [m/min]
     */
    public function getVelocity(?float $effectiveVO2max = null, float $percentage = 1.0): float
    {
        $effectiveVO2max = $effectiveVO2max ?: $this->EffectiveVO2max;
        $effectiveVO2max *= $percentage;

        if ($effectiveVO2max <= 0.0) {
            return 0.0;
        }

        return -876.0 + (876.0 * 876.0 + (4.6 + $effectiveVO2max) / 0.000104) ** 0.5;
    }

    /**
     * @param float $percentage %vVO2max, typically in [0.5, 1.2]
     *
     * @return float [m/min]
     */
    public function getVelocityAt(float $percentage): float
    {
        return $this->getVelocity(null, $percentage);
    }

    /**
     * @param ?float $effectiveVO2max [ml/kg/min]
     * @param float $percentage %vVO2max, typically in [0.5, 1.2]
     *
     * @return float [s/km]
     */
    public function getPace(?float $effectiveVO2max = null, float $percentage = 1.0): float
    {
        $velocity = $this->getVelocity($effectiveVO2max, $percentage);

        if ($velocity <= 0.0) {
            return 0;
        }

        return round(60000 / $velocity);
    }

    /**
     * @param float $percentage %vVO2max, typically in [0.5, 1.2]
     *
     * @return float [s/km]
     */
    public function getPaceAt(float $percentage): float
    {
        return $this->getPace(null, $percentage);
    }
}
