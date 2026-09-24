<?php

/**
 * This file contains class::InverseCalculator
 * @package Runalyze\Calculation\Trimp
 */

namespace Runalyze\Calculation\Trimp;

/**
 * Inverse TRIMP-Calculator
 *
 * TRIMP stands for Training Impulse and gives a measurement for the training impact.
 *
 * This class computes the inverse of the standard Trimp calculator.
 *
 * @see http://fellrnr.com/wiki/TRIMP
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Trimp
 */
class InverseCalculator
{
    protected int $value;

    /**
     * Construct
     * @param int $heartRate [bpm]
     * @param float $trimp value to reach
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected \Runalyze\Athlete $Athlete,
        int $heartRate,
        float $trimp
    ) {
        if ($heartRate <= 0 || $trimp <= 0.0) {
            throw new \InvalidArgumentException('Heart rate and trimp have to be greater than zero.');
        }

        $restingHR = $this->Athlete->knowsRestingHeartRate() ? $this->Athlete->restingHR() : Calculator::DEFAULT_HR_REST;

        if ($heartRate <= $restingHR) {
            throw new \InvalidArgumentException("Heart rate must be greater than the athlete's resting heart rate.");
        }

        $this->calculate($heartRate, $trimp);
    }

    /**
     * Inverse calculation
     *
     * Calculation is done for HR in [bpm] and time is transformed from [s] to [min].
     *
     * @param int $bpm [bpm]
     * @param float $trimp value to reach
     */
    protected function calculate(int $bpm, float $trimp): void
    {
        $Factor = new Factor($this->Athlete->gender());
        $max = $this->Athlete->knowsMaximalHeartRate() ? $this->Athlete->maximalHR() : Calculator::DEFAULT_HR_MAX;
        $rest = $this->Athlete->knowsRestingHeartRate() ? $this->Athlete->restingHR() : Calculator::DEFAULT_HR_REST;

        $hr = max(0, ($bpm - $rest) / ($max - $rest));

        $sum = $trimp / ($hr * exp($Factor->B() * $hr));

        $this->value = (int) round(1 / $Factor->A() * $sum * 60);
    }

    public function value(): int
    {
        return $this->value;
    }
}
