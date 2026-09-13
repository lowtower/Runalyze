<?php

namespace Runalyze\Parser\Activity\Common\Data\Pause;

class Pause
{
    /**
     * @var int|null [bpm]
     */
    protected ?int $HeartRateAtStart = null;

    /**
     * @var int|null [bpm]
     */
    protected ?int $HeartRateAtEnd = null;

    /**
     * @var int|null [bpm]
     */
    protected ?int $HeartRateAtRecovery = null;

    /**
     * @var int|null [s]
     */
    protected ?int $TimeUntilRecovery = null;

    /**
     * @var int|null relative to current baseline
     */
    protected ?int $PerformanceCondition = null;

    public function __construct(
        /**
         * @var int [s]
         */
        protected int $TimeIndex,
        /**
         * @var float [s]
         */
        protected float $Duration
    ) {
    }

    /**
     * @return int [s]
     */
    public function getTimeIndex(): int
    {
        return $this->TimeIndex;
    }

    /**
     * @return float [s]
     */
    public function getDuration(): float
    {
        return $this->Duration;
    }

    /**
     * @param int|null $atStart [bpm]
     * @param int|null $atEnd [bpm]
     */
    public function setHeartRateDetails(?int $atStart, ?int $atEnd): self
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
    public function getHeartRateAtStart(): ?int
    {
        return $this->HeartRateAtStart;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateAtEnd(): ?int
    {
        return $this->HeartRateAtEnd;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateDifference(): ?int
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
    public function setRecoveryDetails(?int $heartRate, ?int $timeUntilRecovery): self
    {
        $this->HeartRateAtRecovery = $heartRate;
        $this->TimeUntilRecovery = $timeUntilRecovery;

        return $this;
    }

    /**
     * @return int|null [bpm]
     */
    public function getHeartRateAtRecovery(): ?int
    {
        return $this->HeartRateAtRecovery;
    }

    /**
     * @return int|null [s]
     */
    public function getTimeUntilRecovery(): ?int
    {
        return $this->TimeUntilRecovery;
    }

    /**
     * @param int|null $performanceCondition relative to current baseline
     */
    public function setPerformanceCondition(?int $performanceCondition): self
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
    public function getPerformanceCondition(): ?int
    {
        return $this->PerformanceCondition;
    }
}
