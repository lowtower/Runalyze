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

    protected HeartRateUnit $PreferredUnit;

    public function __construct(
        ?float $value,
        protected ?Athlete $Athlete = null
    ) {
        $this->setBPM($value);
        $this->PreferredUnit = Configuration::General()->heartRateUnit();
    }

    public function setBPM(?float $value): void
    {
        $this->value = $value;
    }

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

    public function asBPM(): string
    {
        return $this->inBPM() . '&nbsp;bpm';
    }

    /**
     * As [%HRmax]
     * Check first 'canShowInHRmax'!
     */
    public function asHRmax(): string
    {
        return $this->inHRmax() . '&nbsp;%';
    }

    /**
     * As [%HRrest]
     * Check first 'canShowInHRrest'!
     */
    public function asHRrest(): string
    {
        return $this->inHRrest() . '&nbsp;%';
    }

    /**
     * Value in [bpm]
     */
    public function inBPM(): ?int
    {
        return $this->value !== null ? (int) round($this->value) : null;
    }

    /**
     * Value in [%HRmax]
     */
    public function inHRmax(): ?float
    {
        return $this->value !== null && $this->knowsAthlete() ? round(100 * $this->value / $this->Athlete->maximalHR()) : null;
    }

    /**
     * Value in [%HRrest]
     */
    public function inHRrest(): ?float
    {
        return $this->value !== null && $this->knowsAthlete() ? round(100 * ($this->value - $this->Athlete->restingHR()) / ($this->Athlete->maximalHR() - $this->Athlete->restingHR())) : null;
    }

    /**
     * Value in [%] depending on preferred unit
     */
    public function inPercent(): ?float
    {
        if ($this->PreferredUnit->isHRreserve() && $this->canShowInHRrest()) {
            return $this->inHRrest();
        }

        return $this->inHRmax();
    }

    public function canShowInHRmax(): bool
    {
        return $this->knowsAthlete() && $this->Athlete->knowsMaximalHeartRate();
    }

    public function canShowInHRrest(): bool
    {
        return $this->knowsAthlete() && $this->Athlete->knowsRestingHeartRate();
    }

    public function getHRmax(): ?int
    {
        return $this->knowsAthlete() ? $this->Athlete->maximalHR() : null;
    }

    public function getHRrest(): ?int
    {
        return $this->knowsAthlete() ? $this->Athlete->restingHR() : null;
    }

    protected function knowsAthlete(): bool
    {
        return $this->Athlete instanceof Athlete;
    }
}
