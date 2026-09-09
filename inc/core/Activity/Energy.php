<?php

/**
 * This file contains class::Energy
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\EnergyUnit;

/**
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Activity
 */
class Energy implements ValueInterface
{
    /**
     * Default kj multiplier
     * @var float
     */
    public const KJ_MULTIPLIER = 4.1868;

    /**
     * Energy [kcal]
     */
    protected int $Energy;

    /**
     * Preferred unit
     */
    protected EnergyUnit $PreferredUnit;

    /**
     * @param float $energy [kcal]
     * @param bool $withUnit [optional] with or without unit
     */
    public static function format(float $energy, bool $withUnit = true): string
    {
        return (new self($energy))->string($withUnit);
    }

    /**
     * @param float $energy [kcal]
     */
    public function __construct(float $energy = 0, ?EnergyUnit $preferredUnit = null)
    {
        $this->PreferredUnit = ($preferredUnit instanceof \Runalyze\Parameter\Application\EnergyUnit) ? $preferredUnit : Configuration::General()->energyUnit();

        $this->set($energy);
    }

    public function label(): string
    {
        return __('Energy');
    }

    public function unit(): string
    {
        return $this->PreferredUnit->unit();
    }

    /**
     * Set energy
     * @param float $energy [kg]
     * @return \Runalyze\Activity\Energy $this-reference
     */
    public function set($energy): self
    {
        $this->Energy = (int) $energy;

        return $this;
    }

    /**
     * Set energy in KJ
     * @param float $energy [kj]
     * @return \Runalyze\Activity\Energy $this-reference
     */
    public function setKJ(float $energy): self
    {
        $this->Energy = (int) ($energy / self::KJ_MULTIPLIER);
        return $this;
    }

    /**
     * @param float $energy [mixed unit]
     * @return \Runalyze\Activity\Energy $this-reference
     */
    public function setInPreferredUnit(float $energy): self
    {
        if ($this->PreferredUnit->isKJ()) {
            $this->setKJ($energy);
        } else {
            $this->set($energy);
        }

        return $this;
    }

    /**
     * Format energy as string
     * @param bool $withUnit [optional] show unit?
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->PreferredUnit->isKJ()) {
            return $this->stringKJ($withUnit);
        }

        return $this->stringKcal($withUnit);
    }

    /**
     * @return int [kcal]
     */
    public function value(): int
    {
        return $this->Energy;
    }

    /**
     * @return int [kcal]
     */
    public function kcal(): int
    {
        return $this->Energy;
    }

    /**
     * Energy in Kilojule
     * @return int [kj]
     */
    public function kj(): int
    {
        return (int) round($this->Energy * self::KJ_MULTIPLIER);
    }

    /**
     * @return int [mixed unit]
     */
    public function valueInPreferredUnit(): int
    {
        if ($this->PreferredUnit->isKJ()) {
            return $this->kj();
        }

        return $this->kcal();
    }

    /**
     * String: as kcal
     * @param bool $withUnit [optional] show unit?
     * @return string with unit
     */
    public function stringKcal(bool $withUnit = true): string
    {
        return number_format($this->Energy, 0, '.', '') . ($withUnit ? '&nbsp;' . EnergyUnit::KCAL : '');
    }

    /**
     * String: as kj
     * @param bool $withUnit [optional] show unit?
     * @return string with unit
     */
    public function stringKJ(bool $withUnit = true): string
    {
        return number_format($this->kj(), 0, '.', '') . ($withUnit ? '&nbsp;' . EnergyUnit::KJ : '');
    }
}
