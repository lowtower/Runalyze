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
     * @var float
     */
    protected $Distance = 0;

    /**
     * Time [s]
     * @var int
     */
    protected $Time = 0;

    /**
     * @var LapIntensity
     */
    protected $Intensity;

    /**
     * Construct
     * @param string|float $dataOrDistance
     * @param int $time [optional]
     * @param bool $active [optional]
     */
    public function __construct($dataOrDistance = '', $time = null, $active = true)
    {
        if ($time === null) {
            parent::__construct($dataOrDistance);
        } else {
            $this->Distance = (float) $dataOrDistance;
            $this->Time = (int) $time;
            if ($active) {
                $this->Intensity = LapIntensity::getInstanceActive();
            } else {
                $this->Intensity = LapIntensity::getInstanceRest();
            }
        }
    }

    /**
     * @param string $string
     */
    public function fromString($string): void
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
     * @param float $kilometer
     */
    public function setDistance($kilometer): void
    {
        $this->Distance = $kilometer;
    }

    /**
     * @param int $seconds
     */
    public function setTime($seconds): void
    {
        $this->Time = $seconds;
    }

    /**
     * Set resting flag
     * @param bool $flag
     */
    public function setResting($flag = true): void
    {
        $this->Intensity = $flag ? LapIntensity::getInstanceRest() : LapIntensity::getInstanceActive();
    }

    /**
     * @return float
     */
    public function distance()
    {
        return $this->Distance;
    }

    /**
     * @return int
     */
    public function time()
    {
        return $this->Time;
    }

    /**
     * @param int $legacyPaceUnitEnum [optional]
     */
    public function pace($legacyPaceUnitEnum = PaceUnit::MIN_PER_KM): \Runalyze\Activity\Pace
    {
        return new Pace($this->Time, $this->Distance, $legacyPaceUnitEnum);
    }

    /**
     * @return LapIntensity
     */
    public function getIntensity()
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
