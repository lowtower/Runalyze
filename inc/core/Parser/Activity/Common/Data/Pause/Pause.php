<?php

namespace Runalyze\Parser\Activity\Common\Data\Pause;

class Pause
{
    /**
     * @var int [s]
     */
    protected $TimeIndex;

    /**
     * @var int [s]
     */
    protected $Duration;

    /**
     * @var int|null [bpm]
     */
    protected $HeartRateAtStart;

    /**
     * @var int|null [bpm]
     */
    protected $HeartRateAtEnd;

    /**
     * @var int|null [bpm]
     */
    protected $HeartRateAtRecovery;

    /**
     * @var int|null [s]
     */
    protected $TimeUntilRecovery;

    /**
     * @var int|null relative to current baseline
     */
    protected $PerformanceCondition;

    /**
     * @param int $timeIndex
     * @param int $duration
     */
    public function __construct($timeIndex, $duration)
    {
        $this->TimeIndex = $timeIndex;
        $this->Duration = $duration;
    }

    /**
     * @return int [s]
     */
    public function getTimeIndex()
    {
        return $this->TimeIndex;
    }

    /**
     * @return int [s]
     */
    public function getDuration()
    {
        return $this->Duration;
    }

    /**
     * @param int|null $atStart [bpm]
     * @param int|null $atEnd [bpm]
     */
    public function setHeartRateDetails($atStart, $atEnd): self
    {
        $this->HeartRateAtStart = $atStart;
        $this->HeartRateAtEnd = $atEnd;

        return $this;
    }

    public function hasHeartRateDetails(): bool
    {
        return $this->HeartRateAtStart !== null && $this->HeartRateAtEnd !== null;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateAtStart()
    {
        return $this->HeartRateAtStart;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateAtEnd()
    {
        return $this->HeartRateAtEnd;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateDifference()
    {
        return $this->HeartRateAtStart - $this->HeartRateAtEnd;
    }

    public function hasRecoveryDetails(): bool
    {
        return $this->HeartRateAtRecovery !== null && $this->TimeUntilRecovery !== null;
    }

    /**
     * @param int|null $heartRate [bpm]
     * @param int|null $timeUntilRecovery [s]
     */
    public function setRecoveryDetails($heartRate, $timeUntilRecovery): self
    {
        $this->HeartRateAtRecovery = $heartRate;
        $this->TimeUntilRecovery = $timeUntilRecovery;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateAtRecovery()
    {
        return $this->HeartRateAtRecovery;
    }

    /**
     * @return int|null [s]
     */
    public function getTimeUntilRecovery()
    {
        return $this->TimeUntilRecovery;
    }

    /**
     * @param int|null $performanceCondition relative to current baseline
     */
    public function setPerformanceCondition($performanceCondition): self
    {
        $this->PerformanceCondition = $performanceCondition;

        return $this;
    }

    public function hasPerformanceCondition(): bool
    {
        return $this->PerformanceCondition !== null;
    }

    /**
     * @return int|null relative to current baseline
     */
    public function getPerformanceCondition()
    {
        return $this->PerformanceCondition;
    }
}
