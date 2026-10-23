<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data\Round;

use Runalyze\Activity\LapIntensity;

class Round
{
    /**
     * @var float [km]
     */
    protected float $Distance;

    /**
     * @var float [s]
     */
    protected float $Duration;

    protected LapIntensity $Intensity;

    /**
     * @param float $distance [km]
     * @param float $duration [s]
     */
    public function __construct(float $distance, float $duration, bool $isActive = true)
    {
        $this->setDistance($distance);
        $this->setDuration($duration);
        if ($isActive) {
            $this->Intensity = LapIntensity::getInstanceActive();
        } else {
            $this->Intensity = LapIntensity::getInstanceRest();
        }
    }

    public function setIntensity(LapIntensity $intensity): void
    {
        $this->Intensity = $intensity;
    }

    public function getIntensity(): LapIntensity
    {
        return $this->Intensity;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance): void
    {
        $this->Distance = $distance;
    }

    /**
     * @return float [km]
     */
    public function getDistance(): float
    {
        return $this->Distance;
    }

    /**
     * @param float $duration [s]
     */
    public function setDuration(float $duration): void
    {
        $this->Duration = $duration;
    }

    /**
     * @return float [s]
     */
    public function getDuration(): float
    {
        return $this->Duration;
    }

    public function roundDuration(): void
    {
        $this->Duration = round($this->Duration);
    }

    public function setActive(bool $flag = true): void
    {
        $this->Intensity = $flag ? LapIntensity::getInstanceActive() : LapIntensity::getInstanceRest();
    }

    public function isActive(): bool
    {
        return $this->Intensity->isNotRest();
    }

    public function isEqualTo(self $other): bool
    {
        return
            $this->getDuration() === $other->getDuration()
            && $this->getDistance() === $other->getDistance()
            && $this->getIntensity()
                ->isEqualTo($other->getIntensity())
        ;
    }
}
