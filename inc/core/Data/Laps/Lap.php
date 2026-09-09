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

    protected \Runalyze\Activity\Duration $TrackDurationAtEnd;

    protected \Runalyze\Activity\Distance $TrackDistanceAtEnd;

    protected \Runalyze\Activity\HeartRate $HRavg;

    protected \Runalyze\Activity\HeartRate $HRmax;

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
    public function setDuration(float $duration)
    {
        $this->LapDuration = new Duration($duration);
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance)
    {
        $this->LapDistance = new Distance($distance);
    }

    public function setMode(LapIntensity $mode)
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
    public function setTrackDuration(float $duration)
    {
        $this->TrackDurationAtEnd = new Duration($duration);
    }

    /**
     * @param float $distance [km]
     */
    public function setTrackDistance(float $distance)
    {
        $this->TrackDistanceAtEnd = new Distance($distance);
    }

    public function setHR(?float $avg, ?float $max)
    {
        $this->HRavg = new HeartRate($avg, $this->Athlete);
        $this->HRmax = new HeartRate($max, $this->Athlete);
    }

    /**
     * @param int $up [m]
     * @param int $down [m]
     */
    public function setElevation(int $up, int $down)
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

    public function trackDuration(): \Runalyze\Activity\Duration
    {
        return $this->TrackDurationAtEnd;
    }

    /**
     * @return \Runalyze\Activity\Distance
     */
    public function trackDistance()
    {
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

    public function HRavg(): \Runalyze\Activity\HeartRate
    {
        return $this->HRavg;
    }

    public function HRmax(): \Runalyze\Activity\HeartRate
    {
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

    public function setAdditionalValues(array $data)
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
    public function setAdditionalValue(string $key, $value)
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
