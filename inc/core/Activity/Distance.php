<?php

/**
 * This file contains class::Distance
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

// TODO: use
// Configuration::ActivityView()->decimals()

/**
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Activity
 */
class Distance implements ValueInterface
{
    /**
     * Seperator for decimals
     * @var string
     */
    public static $DecimalSeparator = ',';

    /**
     * Seperator for thousands
     * @var string
     */
    public static $ThousandsSeparator = '.';

    /**
     * Default number of decimals
     * **If you change this, also change the test src/CoreBundle/Form/Type/DistanceType.php**
     * @var int
     */
    public static $DefaultDecimals = 3;

    /**
     * For the split overview table use precision 2 for better "overview".
     * @var int
     */
    public static $DefaultDecimalsSplitTable = 2;

    /**
     * Distance [km]
     */
    protected float $Kilometer;

    /**
     * @var \Runalyze\Parameter\Application\DistanceUnitSystem
     */
    protected $UnitSystem;

    /**
     * @param float $km [km]
     * @param bool $withUnit [optional] with or without unit
     * @param int|bool $decimals [optional] number of decimals
     */
    public static function format(float $km, bool $withUnit = true, $decimals = false): string
    {
        return (new self($km))->string($withUnit, $decimals);
    }

    public function __construct(float $km = 0.0, ?DistanceUnitSystem $unitSystem = null)
    {
        $this->Kilometer = $km;
        $this->UnitSystem = ($unitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
    }

    /**
     * Label for distance
     */
    public function label(): string
    {
        return __('Distance');
    }

    /**
     * Set distance
     * @param float $km [km]
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function set($km): self
    {
        $this->Kilometer = (float) str_replace(',', '.', $km);

        return $this;
    }

    /**
     * Set distance in miles
     * @param float $miles [mi]
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function setMiles(float $miles): self
    {
        $this->Kilometer = (float) str_replace(',', '.', $miles) / DistanceUnitSystem::MILE_MULTIPLIER;

        return $this;
    }

    /**
     * @param float $distance [mixed unit]
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function setInPreferredUnit(float $distance): self
    {
        if ($this->UnitSystem->isImperial()) {
            $this->setMiles($distance);
        } else {
            $this->set($distance);
        }

        return $this;
    }

    /**
     * Get distance
     * @return float [km]
     */
    public function value(): float
    {
        return $this->Kilometer;
    }

    /**
     * @return float [km]
     */
    public function kilometer(): float
    {
        return $this->Kilometer;
    }

    /**
     * @return int [m]
     */
    public function meter(): int
    {
        return (int) round($this->Kilometer * 1000);
    }

    /**
     * Miles
     */
    public function miles(): float
    {
        return $this->Kilometer * DistanceUnitSystem::MILE_MULTIPLIER;
    }

    /**
     * Yards
     */
    public function yards(): int
    {
        return (int) round($this->Kilometer * DistanceUnitSystem::YARD_MULTIPLIER);
    }

    /**
     * @return int [feet]
     */
    public function feet(): int
    {
        return (int) round($this->Kilometer * DistanceUnitSystem::FEET_MULTIPLIER);
    }

    public function unit(): string
    {
        if ($this->UnitSystem->isImperial()) {
            return DistanceUnitSystem::MILES;
        }

        return DistanceUnitSystem::KM;
    }

    /**
     * @return float [mixed unit]
     */
    public function valueInPreferredUnit(): float
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->miles();
        }

        return $this->kilometer();
    }

    /**
     * Format distance as string
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional] number of decimals
     */
    public function string(bool $withUnit = true, $decimals = false): string
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->stringMiles($withUnit, $decimals);
        }

        return $this->stringKilometer($withUnit, $decimals);
    }

    /**
     * Format distance with auto detection for track distances shown in meter
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional] number of decimals
     */
    public function stringAuto(bool $withUnit = true, $decimals = false): string
    {
        if ($this->Kilometer <= 1.0 || $this->Kilometer === 1.5 || $this->Kilometer === 3.0) {
            return $this->stringMeter($withUnit, $decimals);
        }

        if ($this->Kilometer === 5.0 || $this->Kilometer === 10.0) {
            return $this->stringKilometer($withUnit, $decimals);
        }

        return $this->string($withUnit, $decimals);
    }

    /**
     * String: as kilometer
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional]
     * @return string with unit
     */
    public function stringKilometer(bool $withUnit = true, $decimals = false): string
    {
        if ($decimals === false) {
            $decimals = self::$DefaultDecimals;
        }

        return number_format($this->Kilometer, $decimals, self::$DecimalSeparator, self::$ThousandsSeparator) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::KM : '');
    }

    /**
     * String: as meter
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional]
     * @return string with unit
     */
    public function stringMeter(bool $withUnit = true, $decimals = false): string
    {
        return number_format($this->Kilometer * 1000, 0, '', '.') . ($withUnit ? DistanceUnitSystem::METER : '');
    }

    /**
     * String: as feet
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional]
     * @return string with unit
     */
    public function stringFeet(bool $withUnit = true, $decimals = false): string
    {
        return number_format($this->Kilometer * DistanceUnitSystem::FEET_MULTIPLIER, 0, '', '.') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::FEET : '');
    }

    /**
     * String: as yards
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional]
     * @return string with unit
     */
    public function stringYards(bool $withUnit = true, $decimals = false): string
    {
        return number_format($this->Kilometer * DistanceUnitSystem::YARD_MULTIPLIER, 0, '', '.') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::YARDS : '');
    }

    /**
     * String: as miles
     * @param bool $withUnit [optional]
     * @param int|bool $decimals [optional]
     * @return string with unit
     */
    public function stringMiles(bool $withUnit = true, $decimals = false): string
    {
        if ($decimals === false) {
            $decimals = self::$DefaultDecimals;
        }

        return number_format($this->Kilometer * DistanceUnitSystem::MILE_MULTIPLIER, $decimals, '.', ',') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::MILES : '');
    }

    /**
     * Multiply distance
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function multiply(float $factor): self
    {
        $this->Kilometer *= $factor;

        return $this;
    }

    /**
     * Add another distance
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function add(self $object): self
    {
        $this->Kilometer += $object->kilometer();

        return $this;
    }

    /**
     * Subtract another distance
     * @return \Runalyze\Activity\Distance $this-reference
     */
    public function subtract(self $object): self
    {
        $this->Kilometer -= $object->kilometer();

        return $this;
    }

    /**
     * Is distance negative?
     */
    public function isNegative(): bool
    {
        return $this->Kilometer < 0;
    }

    /**
     * Is distance zero?
     */
    public function isZero(): bool
    {
        return $this->Kilometer == 0;
    }
}
