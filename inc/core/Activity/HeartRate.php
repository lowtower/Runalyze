<?php

/**
 * This file contains class::HeartRate
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Athlete;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\HeartRateUnit;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class HeartRate
{
    /**
     * Value [bpm]
     */
    protected ?float $value = null;

    /**
     * Preferred heart rate unit
     */
    protected HeartRateUnit $PreferredUnit;

    public function __construct(
        ?float $value, /**
     * Athlete
     */
        protected ?Athlete $Athlete = null
    ) {
        $this->setBPM($value);
        $this->PreferredUnit = Configuration::General()->heartRateUnit();
    }

    /**
     * Set value
     */
    public function setBPM(?float $value): void
    {
        $this->value = $value;
    }

    /**
     * Heart rate as string
     */
    public function string(): string
    {
        if ($this->PreferredUnit->isHRreserve() && $this->canShowInHRrest()) {
            return $this->asHRrest();
        }

        if ($this->PreferredUnit->isHRmax() && $this->canShowInHRmax()) {
            return $this->asHRmax();
        }

        return $this->asBPM();
    }

    /**
     * As bpm
     */
    public function asBPM(): string
    {
        return $this->inBPM() . '&nbsp;bpm';
    }

    /**
     * As [%HRmax]
     * Check first 'canShowInHRMax'!
     */
    public function asHRmax(): string
    {
        return $this->inHRmax() . '&nbsp;&#37;';
    }

    /**
     * As [%HRrest]
     * Check first 'canShowInHRMax'!
     */
    public function asHRrest(): string
    {
        return $this->inHRrest() . '&nbsp;&#37;';
    }

    /**
     * Value in [bpm]
     */
    public function inBPM(): int
    {
        return (int) round($this->value);
    }

    /**
     * Value in [%HRmax]
     */
    public function inHRmax(): float
    {
        return round(100 * $this->value / $this->Athlete->maximalHR());
    }

    /**
     * Value in [%HRrest]
     */
    public function inHRrest(): float
    {
        return round(100 * ($this->value - $this->Athlete->restingHR()) / ($this->Athlete->maximalHR() - $this->Athlete->restingHR()));
    }

    /**
     * Value in [%] depending on preferred unit
     */
    public function inPercent(): float
    {
        if ($this->PreferredUnit->isHRreserve() && $this->canShowInHRrest()) {
            return $this->inHRrest();
        }

        return $this->inHRmax();
    }

    /**
     * Can show in HRmax?
     */
    public function canShowInHRmax(): bool
    {
        return $this->knowsAthlete() && $this->Athlete->knowsMaximalHeartRate();
    }

    /**
     * Can show in HRrest?
     */
    public function canShowInHRrest(): bool
    {
        return $this->knowsAthlete() && $this->Athlete->knowsRestingHeartRate();
    }

    /**
     * HRmax from the Athlete
     * #TSC
     */
    public function getHRmax(): ?int
    {
        return $this->knowsAthlete() ? $this->Athlete->maximalHR() : null;
    }

    /**
     * HRrest from the Athlete
     * #TSC
     */
    public function getHRrest(): ?int
    {
        return $this->knowsAthlete() ? $this->Athlete->restingHR() : null;
    }

    /**
     * Is the athlete known?
     */
    protected function knowsAthlete(): bool
    {
        return $this->Athlete instanceof Athlete;
    }
}
