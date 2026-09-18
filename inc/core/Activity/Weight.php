<?php

/**
 * This file contains class::Weight
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\WeightUnit;

/**
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Activity
 */
class Weight implements ValueInterface
{
    /**
     * Default poun d multiplier
     * @var float
     */
    public const POUNDS_MULTIPLIER = 2.204622;

    /**
     * Default stone multiplier
     * @var float
     */
    public const STONES_MULTIPLIER = 0.157473;

    /**
     * Default number of decimals
     * @var int
     */
    public static $DefaultDecimals = 1;

    /**
     * Weight [kg]
     */
    protected float $Weight;

    /**
     * Preferred unit
     */
    protected WeightUnit $PreferredUnit;

    /**
     * @param float $weight [kg]
     * @param bool $withUnit [optional] with or without unit
     * @param int|bool $decimals [optional] number of decimals
     */
    public static function format(float $weight, bool $withUnit = true, $decimals = false): string
    {
        return (new self($weight))->string($withUnit, $decimals);
    }

    /**
     * @param float $weight [kg]
     */
    public function __construct(float $weight = 0, ?WeightUnit $preferredUnit = null)
    {
        $this->PreferredUnit = ($preferredUnit instanceof \Runalyze\Parameter\Application\WeightUnit) ? $preferredUnit : Configuration::General()->weightUnit();

        $this->set($weight);
    }

    public function label(): string
    {
        return __('Weight');
    }

    public function unit(): string
    {
        return $this->PreferredUnit->unit();
    }

    /**
     * Set weight
     * @param float $weight [kg]
     * @return \Runalyze\Activity\Weight $this-reference
     */
    public function set($weight): self
    {
        $this->Weight = (float) str_replace(',', '.', (string) $weight);

        return $this;
    }

    /**
     * Set weight in pounds
     * @param float $weight [pounds]
     * @return \Runalyze\Activity\Weight $this-reference
     */
    public function setPounds(float $weight): self
    {
        $this->Weight = (float) str_replace(',', '.', (string) $weight) / self::POUNDS_MULTIPLIER;

        return $this;
    }

    /**
     * Set weight in stones
     * @param float $weight [stones]
     * @return \Runalyze\Activity\Weight $this-reference
     */
    public function setStones(float $weight): self
    {
        $this->Weight = (float) str_replace(',', '.', (string) $weight) / self::STONES_MULTIPLIER;

        return $this;
    }

    /**
     * @param float $weight [mixed unit]
     * @return \Runalyze\Activity\Weight $this-reference
     */
    public function setInPreferredUnit(float $weight): self
    {
        if ($this->PreferredUnit->isPounds()) {
            $this->setPounds($weight);
        } elseif ($this->PreferredUnit->isStones()) {
            $this->setStones($weight);
        } else {
            $this->set($weight);
        }

        return $this;
    }

    /**
     * Format weight as string
     * @param bool $withUnit [optional] show unit?
     * @param bool|int $decimals [optional] number of decimals
     */
    public function string(bool $withUnit = true, $decimals = false): string
    {
        if ($this->PreferredUnit->isPounds()) {
            return $this->stringPounds($withUnit, $decimals);
        }

        if ($this->PreferredUnit->isStones()) {
            return $this->stringStones($withUnit, $decimals);
        }

        return $this->stringKG($withUnit, $decimals);
    }

    /**
     * @return float [kg]
     */
    public function value(): float
    {
        return $this->Weight;
    }

    /**
     * @return float [kg]
     */
    public function kg(): float
    {
        return $this->Weight;
    }

    /**
     * Weight in pounds
     * @return float [lbs]
     */
    public function pounds(): float
    {
        return $this->Weight * self::POUNDS_MULTIPLIER;
    }

    /**
     * Weight in stones
     * @return float [st]
     */
    public function stones(): float
    {
        return $this->Weight * self::STONES_MULTIPLIER;
    }

    /**
     * @return float [mixed unit]
     */
    public function valueInPreferredUnit(): float
    {
        if ($this->PreferredUnit->isPounds()) {
            return $this->pounds();
        }

        if ($this->PreferredUnit->isStones()) {
            return $this->stones();
        }

        return $this->kg();
    }

    /**
     * String: as kg
     * @param bool $withUnit [optional] show unit?
     * @param bool|int $decimals [optional] number of decimals
     * @return string with unit
     */
    public function stringKG(bool $withUnit = true, $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->Weight, $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::KG : '');
    }

    /**
     * String: as pounds
     * @param bool $withUnit [optional] show unit?
     * @param bool|int $decimals [optional] number of decimals
     * @return string with unit
     */
    public function stringPounds(bool $withUnit = true, $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->pounds(), $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::POUNDS : '');
    }

    /**
     * String: as stone
     * @param bool $withUnit [optional] show unit?
     * @param bool|int $decimals [optional] number of decimals
     * @return string with unit
     */
    public function stringStones(bool $withUnit = true, $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->stones(), $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::STONES : '');
    }
}
