<?php

/**
 * This file contains class::Split
 * @package Runalyze\Model\Activity\Splits
 */

namespace Runalyze\Model\Activity\Splits;

use Runalyze\Activity\Duration;
use Runalyze\Activity\LapIntensity;
use Runalyze\Activity\Pace;
use Runalyze\Model\StringObject;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Single split
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
class Split extends StringObject
{
    /**
     * Separator
     * @var string
     */
    public const SEPARATOR = '|';

    /**
     * Resting flag
     * @var string
     */
    public const RESTING = 'R';

    /**
     * Distance [km]
     */
    protected float $Distance = 0;

    /**
     * Time [s]
     */
    protected int $Time = 0;

    protected LapIntensity $Intensity;

    /**
     * Construct
     * @param string|float $dataOrDistance
     */
    public function __construct($dataOrDistance = '', ?int $time = null, bool $active = true)
    {
        if ($time === null) {
            parent::__construct($dataOrDistance);
        } else {
            $this->Distance = (float) $dataOrDistance;
            $this->Time = $time;
            if ($active) {
                $this->Intensity = LapIntensity::getInstanceActive();
            } else {
                $this->Intensity = LapIntensity::getInstanceRest();
            }
        }
    }

    public function fromString(string $string): void
    {
        $intensity = LapIntensity::fromValue(substr($string, 0, 1));
        if ($intensity instanceof \Runalyze\Activity\LapIntensity) {
            $this->Intensity = $intensity;
            $string = substr($string, 1);
        } else {
            $this->Intensity = LapIntensity::getInstanceActive();
        }

        $Duration = new Duration(substr(strrchr($string, self::SEPARATOR), 1));

        $this->Distance = (float) strstr($string, self::SEPARATOR, true);
        $this->Time = (int) $Duration->seconds();
    }

    /**
     * As string
     */
    public function asString(): string
    {
        $string = $this->Intensity->getValue();
        $string .= $this->distanceAsString();
        $string .= self::SEPARATOR;

        return $string . $this->timeAsString();
    }

    /**
     * Is empty
     */
    public function isEmpty(): bool
    {
        return $this->Distance <= 0 && $this->Time <= 0;
    }

    /**
     * Set kilometer
     */
    public function setDistance(float $kilometer): void
    {
        $this->Distance = $kilometer;
    }

    public function setTime(int $seconds): void
    {
        $this->Time = $seconds;
    }

    /**
     * Set resting flag
     */
    public function setResting(bool $flag = true): void
    {
        $this->Intensity = $flag ? LapIntensity::getInstanceRest() : LapIntensity::getInstanceActive();
    }

    public function distance(): float
    {
        return $this->Distance;
    }

    public function time(): int
    {
        return $this->Time;
    }

    public function pace(int $legacyPaceUnitEnum = PaceUnit::MIN_PER_KM): Pace
    {
        return new Pace($this->Time, $this->Distance, $legacyPaceUnitEnum);
    }

    public function getIntensity(): LapIntensity
    {
        return $this->Intensity;
    }

    /**
     * Is active?
     */
    public function isActive(): bool
    {
        return $this->Intensity->isNotRest();
    }

    /**
     * Format distance
     */
    private function distanceAsString(): string
    {
        return number_format($this->Distance, 3, '.', '');
    }

    /**
     * Format time
     */
    private function timeAsString(): string
    {
        return Duration::format($this->Time);
    }
}
