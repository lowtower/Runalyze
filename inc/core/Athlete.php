<?php

declare(strict_types=1);

namespace Runalyze;

use Runalyze\Profile\Athlete\Gender;

class Athlete
{
    protected int $Gender;

    /**
     * @var null|int [bpm]
     */
    protected ?int $maximalHR = null;

    /**
     * @var null|int [bpm]
     */
    protected ?int $restingHR = null;

    /**
     * @var null|float [kg]
     */
    protected ?float $weight = null;

    protected ?int $birthyear = null;

    /**
     * @var float [ml/kg/min]
     */
    protected float $vo2max = 0.0;

    /**
     * @param null|int $Gender see \Runalyze\Profile\Athlete\Gender
     * @param null|int $maximalHR [bpm]
     * @param null|int $restingHR [bpm]
     * @param null|float $weight [kg]
     * @param float $vo2max [ml/kg/min]
     */
    public function __construct(
        ?int $Gender = null,
        ?int $maximalHR = null,
        ?int $restingHR = null,
        ?float $weight = null,
        ?int $birthyear = null,
        float $vo2max = 0.0
    ) {
        $this->Gender = $Gender ?: Gender::NONE;
        $this->maximalHR = $maximalHR;
        $this->restingHR = $restingHR;
        $this->weight = $weight;
        $this->birthyear = $birthyear;
        $this->vo2max = $vo2max;
    }

    /**
     * @see \Runalyze\Profile\Athlete\Gender
     */
    public function gender(): ?int
    {
        return $this->Gender;
    }

    /**
     * @return null|int [bpm]
     */
    public function maximalHR(): ?int
    {
        return $this->maximalHR;
    }

    /**
     * @return null|int [bpm]
     */
    public function restingHR(): ?int
    {
        return $this->restingHR;
    }

    /**
     * @return null|float [kg]
     */
    public function weight(): ?float
    {
        return $this->weight;
    }

    /**
     * @return null|int [years]
     */
    public function age(): ?int
    {
        return ($this->birthyear !== null) ? (int) date('Y') - $this->birthyear : null;
    }

    public function birthyear(): ?int
    {
        return $this->birthyear;
    }

    public function vo2max(): float
    {
        return $this->vo2max;
    }

    public function knowsGender(): bool
    {
        return $this->Gender !== Gender::NONE && $this->Gender !== null;
    }

    public function isMale(): bool
    {
        return $this->Gender === Gender::MALE;
    }

    public function isFemale(): bool
    {
        return $this->Gender === Gender::FEMALE;
    }

    public function knowsMaximalHeartRate(): bool
    {
        return $this->maximalHR !== null;
    }

    public function knowsRestingHeartRate(): bool
    {
        return $this->restingHR !== null;
    }

    public function knowsWeight(): bool
    {
        return $this->weight !== null;
    }

    public function knowsBirthyear(): bool
    {
        return $this->birthyear !== null;
    }

    public function knowsAge(): bool
    {
        return $this->birthyear !== null;
    }

    public function knowsEffectiveVO2max(): bool
    {
        return $this->vo2max !== 0.0;
    }
}
