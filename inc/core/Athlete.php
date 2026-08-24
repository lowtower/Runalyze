<?php

declare(strict_types=1);

namespace Runalyze;

use Runalyze\Profile\Athlete\Gender;

class Athlete
{
    /**
     * @var int
     */
    protected $Gender;

    /**
     * @var null|int [bpm]
     */
    protected $maximalHR;

    /**
     * @var null|int [bpm]
     */
    protected $restingHR;

    /**
     * @var null|float [kg]
     */
    protected $weight;

    /**
     * @var null|int
     */
    protected $birthyear;

    /**
     * @var float [ml/kg/min]
     */
    protected $vo2max = 0.0;

    /**
     * @param null|int $Gender see \Runalyze\Profile\Athlete\Gender
     * @param null|int $maximalHR [bpm]
     * @param null|int $restingHR [bpm]
     * @param null|float $weight [kg]
     * @param null|int $birthyear
     * @param float $vo2max [ml/kg/min]
     */
    public function __construct(
        $Gender = null,
        $maximalHR = null,
        $restingHR = null,
        $weight = null,
        $birthyear = null,
        $vo2max = 0.0
    )
    {
        $this->Gender = $Gender ?: Gender::NONE;
        $this->maximalHR = $maximalHR;
        $this->restingHR = $restingHR;
        $this->weight = $weight;
        $this->birthyear = $birthyear;
        $this->vo2max = $vo2max;
    }

    /**
     * @see \Runalyze\Profile\Athlete\Gender
     * @return null|int
     */
    public function gender()
    {
        return $this->Gender;
    }

    /**
     * @return null|int [bpm]
     */
    public function maximalHR()
    {
        return $this->maximalHR;
    }

    /**
     * @return null|int [bpm]
     */
    public function restingHR()
    {
        return $this->restingHR;
    }

    /**
     * @return null|int [kg]
     */
    public function weight()
    {
        return $this->weight;
    }

    /**
     * @return null|int [years]
     */
    public function age()
    {
        return ($this->birthyear !== null) ? date('Y') - $this->birthyear : null;
    }

    /**
     * @return null|int
     */
    public function birthyear()
    {
        return $this->birthyear;
    }

    /**
     * @return float
     */
    public function vo2max()
    {
        return $this->vo2max;
    }

    public function knowsGender(): bool
    {
        return ($this->Gender !== Gender::NONE && $this->Gender !== null);
    }

    public function isMale(): bool
    {
        return $this->Gender == Gender::MALE;
    }

    public function isFemale(): bool
    {
        return $this->Gender == Gender::FEMALE;
    }

    public function knowsMaximalHeartRate(): bool
    {
        return ($this->maximalHR !== null);
    }

    public function knowsRestingHeartRate(): bool
    {
        return ($this->restingHR !== null);
    }

    public function knowsWeight(): bool
    {
        return ($this->weight !== null);
    }

    public function knowsBirthyear(): bool
    {
        return ($this->birthyear !== null);
    }

    public function knowsAge(): bool
    {
        return ($this->birthyear !== null);
    }

    public function knowsEffectiveVO2max(): bool
    {
        return ($this->vo2max !== 0.0);
    }
}
