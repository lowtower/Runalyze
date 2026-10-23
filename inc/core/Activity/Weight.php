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
    public const float POUNDS_MULTIPLIER = 2.204622;

    public const float STONES_MULTIPLIER = 0.157473;

    public static int $DefaultDecimals = 1;

    /**
     * Weight [kg]
     */
    protected float $Weight;

    protected WeightUnit $PreferredUnit;

    /**
     * @param float $weight [kg]
     */
    public static function format(float $weight, bool $withUnit = true, int|bool $decimals = false): string
    {
        return new self($weight)->string($withUnit, $decimals);
    }

    /**
     * @param float $weight [kg]
     */
    public function __construct(float $weight = 0, ?WeightUnit $preferredUnit = null)
    {
        $this->PreferredUnit = $preferredUnit ?? Configuration::General()->weightUnit();

        $this->set($weight);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Weight');
    }

    public function unit(): string
    {
        return $this->PreferredUnit->unit();
    }

    /**
     * @param float $weight [kg]
     */
    public function set(mixed $weight): self
    {
        $this->Weight = (float) str_replace(',', '.', (string) $weight);

        return $this;
    }

    /**
     * @param float $weight [pounds]
     */
    public function setPounds(float $weight): self
    {
        $this->Weight = $weight / self::POUNDS_MULTIPLIER;

        return $this;
    }

    /**
     * @param float $weight [stones]
     */
    public function setStones(float $weight): self
    {
        $this->Weight = $weight / self::STONES_MULTIPLIER;

        return $this;
    }

    /**
     * @param float $weight [mixed unit]
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

    public function string(bool $withUnit = true, int|bool $decimals = false): string
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
     * @return float [lbs]
     */
    public function pounds(): float
    {
        return $this->Weight * self::POUNDS_MULTIPLIER;
    }

    /**
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

    public function stringKG(bool $withUnit = true, int|bool $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->Weight, $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::KG : '');
    }

    public function stringPounds(bool $withUnit = true, int|bool $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->pounds(), $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::POUNDS : '');
    }

    public function stringStones(bool $withUnit = true, int|bool $decimals = false): string
    {
        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->stones(), $decimals, '.', '') . ($withUnit ? '&nbsp;' . WeightUnit::STONES : '');
    }
}
