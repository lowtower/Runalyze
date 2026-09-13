<?php

/**
 * This file contains class::Lap
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\HeartRate;
use Runalyze\Activity\LapIntensity;
use Runalyze\Context;

/**
 * A single lap
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Lap
{
    protected \Runalyze\Activity\Duration $LapDuration;

    protected \Runalyze\Activity\Distance $LapDistance;

    protected ?\Runalyze\Activity\Pace $LapPace = null;

    /**
     * #TSC Mode is the "old" name and i dont want to change it.
     * @var LapIntensity enum
     */
    protected ?LapIntensity $Mode = null;

    protected ?\Runalyze\Activity\Duration $TrackDurationAtEnd = null;

    protected ?\Runalyze\Activity\Distance $TrackDistanceAtEnd = null;

    protected ?\Runalyze\Activity\HeartRate $HRavg = null;

    protected ?\Runalyze\Activity\HeartRate $HRmax = null;

    /**
     * @var int|bool [m]
     */
    protected $ElevationUp = false;

    /**
     * @var int|bool [m]
     */
    protected $ElevationDown = false;

    protected array $AdditionalValues = [];

    protected \Runalyze\Athlete $Athlete;

    public function __construct(float $duration = 0.0, float $distance = 0.0, ?LapIntensity $mode = null)
    {
        if (! $mode instanceof \Runalyze\Activity\LapIntensity) {
            $mode = LapIntensity::getInstanceActive();
        }

        $this->setDuration($duration);
        $this->setDistance($distance);
        $this->setMode($mode);

        $this->Athlete = Context::Athlete();
    }

    /**
     * @param float $duration [s]
     */
    public function setDuration(float $duration): void
    {
        $this->LapDuration = new Duration($duration);
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance): void
    {
        $this->LapDistance = new Distance($distance);
    }

    public function setMode(LapIntensity $mode): void
    {
        $this->Mode = $mode;
    }

    /**
     * @return LapIntensity
     */
    public function getMode()
    {
        return $this->Mode;
    }

    /**
     * @param float $duration [s]
     */
    public function setTrackDuration(float $duration): void
    {
        $this->TrackDurationAtEnd = new Duration($duration);
    }

    /**
     * @param float $distance [km]
     */
    public function setTrackDistance(float $distance): void
    {
        $this->TrackDistanceAtEnd = new Distance($distance);
    }

    public function setHR(?float $avg, ?float $max): void
    {
        $this->HRavg = new HeartRate($avg, $this->Athlete);
        $this->HRmax = new HeartRate($max, $this->Athlete);
    }

    /**
     * @param int $up [m]
     * @param int $down [m]
     */
    public function setElevation(int $up, int $down): void
    {
        $this->ElevationUp = $up;
        $this->ElevationDown = $down;
    }

    public function duration(): \Runalyze\Activity\Duration
    {
        return $this->LapDuration;
    }

    public function distance(): \Runalyze\Activity\Distance
    {
        return $this->LapDistance;
    }

    public function pace(): ?\Runalyze\Activity\Pace
    {
        return $this->LapPace;
    }

    /**
     * @throws \RuntimeException if track duration was never set, check hasTrackValues() first
     */
    public function trackDuration(): \Runalyze\Activity\Duration
    {
        if (! $this->TrackDurationAtEnd instanceof \Runalyze\Activity\Duration) {
            throw new \RuntimeException('Track duration was never set for this lap. Call hasTrackValues() first.');
        }

        return $this->TrackDurationAtEnd;
    }

    /**
     * @throws \RuntimeException if track distance was never set, check hasTrackValues() first
     * @return \Runalyze\Activity\Distance
     */
    public function trackDistance()
    {
        if (! $this->TrackDistanceAtEnd instanceof \Runalyze\Activity\Distance) {
            throw new \RuntimeException('Track distance was never set for this lap. Call hasTrackValues() first.');
        }

        return $this->TrackDistanceAtEnd;
    }

    public function hasTrackValues(): bool
    {
        return $this->TrackDurationAtEnd instanceof \Runalyze\Activity\Duration;
    }

    public function isActive(): bool
    {
        return $this->Mode->isNotRest();
    }

    public function hasHR(): bool
    {
        return $this->HRavg instanceof \Runalyze\Activity\HeartRate;
    }

    /**
     * @throws \RuntimeException if HR was never set, check hasHR() first
     */
    public function HRavg(): \Runalyze\Activity\HeartRate
    {
        if (! $this->HRavg instanceof \Runalyze\Activity\HeartRate) {
            throw new \RuntimeException('HR was never set for this lap. Call hasHR() first.');
        }

        return $this->HRavg;
    }

    /**
     * @throws \RuntimeException if HR was never set, check hasHR() first
     */
    public function HRmax(): \Runalyze\Activity\HeartRate
    {
        if (! $this->HRmax instanceof \Runalyze\Activity\HeartRate) {
            throw new \RuntimeException('HR was never set for this lap. Call hasHR() first.');
        }

        return $this->HRmax;
    }

    public function hasElevation(): bool
    {
        return $this->ElevationUp !== false;
    }

    /**
     * @return int [m]
     */
    public function elevationUp()
    {
        return $this->ElevationUp;
    }

    /**
     * @return int [m]
     */
    public function elevationDown(): int
    {
        return $this->ElevationDown;
    }

    public function setAdditionalValues(array $data): void
    {
        $this->AdditionalValues = $data;
    }

    /**
     * @return array
     */
    public function additionalValues()
    {
        return $this->AdditionalValues;
    }

    /**
     * @param mixed $value
     */
    public function setAdditionalValue(string $key, $value): void
    {
        $this->AdditionalValues[$key] = $value;
    }

    public function hasAdditionalValue(string $key): bool
    {
        return isset($this->AdditionalValues[$key]);
    }

    /**
     * @return mixed
     */
    public function additionalValue(string $key)
    {
        return $this->AdditionalValues[$key];
    }
}
