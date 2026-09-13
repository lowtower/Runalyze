<?php

/**
 * This file contains class::PaceUnit
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

use Runalyze\Activity\PaceUnit as PaceUnitObject;
use Runalyze\Common\Enum\AbstractEnum;

/**
 * @deprecated since v4.1
 */
class PaceUnit extends AbstractEnum
{
    /**
     * Speed unit km/h
     * @var int
     */
    public const KM_PER_H = 0;

    /**
     * Speed unit mph
     * @var int
     */
    public const MILES_PER_H = 1;

    /**
     * Speed unit min/km
     * @var int
     */
    public const MIN_PER_KM = 2;

    /**
     * Speed unit min/mile
     * @var int
     */
    public const MIN_PER_MILE = 3;

    /**
     * Speed unit m/s
     * @var int
     */
    public const M_PER_S = 4;

    /**
     * Speed unit min/100m
     * @var int
     */
    public const MIN_PER_100M = 5;

    /**
     * Speed unit min/100y
     * @var int
     */
    public const MIN_PER_100Y = 6;

    /**
     * Speed unit min/500m
     * @var int
     */
    public const MIN_PER_500M = 7;

    /**
     * Speed unit min/500y
     * @var int
     */
    public const MIN_PER_500Y = 8;

    /**
     * @var \Runalyze\Activity\PaceUnit\AbstractUnit
     */
    protected $UnitObject;

    /**
     * @param int $id id from internal enum
     */
    public static function stringFor($id): string
    {
        return match ($id) {
            self::KM_PER_H => 'km/h',
            self::MILES_PER_H => 'mph',
            self::MIN_PER_KM => 'min/km',
            self::MIN_PER_MILE => 'min/mi',
            self::M_PER_S => 'm/s',
            self::MIN_PER_100M => 'min/100m',
            self::MIN_PER_100Y => 'min/100y',
            self::MIN_PER_500M => 'min/500m',
            self::MIN_PER_500Y => 'min/500y',
            default => throw new \InvalidArgumentException('Invalid pace unit id "' . $id . '".'),
        };
    }

    public static function getChoices(): array
    {
        return [
            self::stringFor(self::KM_PER_H) => self::KM_PER_H,
            self::stringFor(self::MILES_PER_H) => self::MILES_PER_H,
            self::stringFor(self::MIN_PER_KM) => self::MIN_PER_KM,
            self::stringFor(self::MIN_PER_MILE) => self::MIN_PER_MILE,
            self::stringFor(self::M_PER_S) => self::M_PER_S,
            self::stringFor(self::MIN_PER_100M) => self::MIN_PER_100M,
            self::stringFor(self::MIN_PER_100Y) => self::MIN_PER_100Y,
            self::stringFor(self::MIN_PER_500M) => self::MIN_PER_500M,
            self::stringFor(self::MIN_PER_500Y) => self::MIN_PER_500Y,
        ];
    }

    /**
     * Set value
     * @param mixed $id new value
     * @throws \InvalidArgumentException
     */
    public function set($id)
    {
        $this->UnitObject = $this->getNewPaceUnitObject($id);
    }

    /**
     * @param int $id
     * @return \Runalyze\Activity\PaceUnit\AbstractUnit
     */
    protected function getNewPaceUnitObject($id)
    {
        return match ($id) {
            self::MILES_PER_H => new PaceUnitObject\MilesPerHour(),
            self::MIN_PER_KM => new PaceUnitObject\MinPerKilometer(),
            self::MIN_PER_MILE => new PaceUnitObject\MinPerMile(),
            self::M_PER_S => new PaceUnitObject\MeterPerSecond(),
            self::MIN_PER_100M => new PaceUnitObject\MinPer100m(),
            self::MIN_PER_100Y => new PaceUnitObject\MinPer100y(),
            self::MIN_PER_500M => new PaceUnitObject\MinPer500m(),
            self::MIN_PER_500Y => new PaceUnitObject\MinPer500y(),
            default => new PaceUnitObject\KmPerHour(),
        };
    }

    /**
     * Unit object
     * @return \Runalyze\Activity\PaceUnit\AbstractUnit
     */
    public function object()
    {
        return $this->UnitObject;
    }
}
