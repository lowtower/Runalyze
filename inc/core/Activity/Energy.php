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
    public const float KJ_MULTIPLIER = 4.1868;

    /**
     * Energy [kcal]
     */
    protected int $Energy;

    protected EnergyUnit $PreferredUnit;

    /**
     * @param float $energy [kcal]
     */
    public static function format(float $energy, bool $withUnit = true): string
    {
        return new self($energy)->string($withUnit);
    }

    /**
     * @param float $energy [kcal]
     */
    public function __construct(float $energy = 0, ?EnergyUnit $preferredUnit = null)
    {
        $this->PreferredUnit = $preferredUnit ?? Configuration::General()->energyUnit();

        $this->set($energy);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Energy');
    }

    public function unit(): string
    {
        return $this->PreferredUnit->unit();
    }

    public function set(mixed $energy): self
    {
        $this->Energy = (int) round((float) $energy);

        return $this;
    }

    /**
     * @param float $energy [kj]
     */
    public function setKJ(float $energy): self
    {
        $this->Energy = (int) round($energy / self::KJ_MULTIPLIER);

        return $this;
    }

    /**
     * @param float $energy [mixed unit]
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

    public function stringKcal(bool $withUnit = true): string
    {
        return number_format($this->Energy, 0, '.', '') . ($withUnit ? '&nbsp;' . EnergyUnit::KCAL : '');
    }

    public function stringKJ(bool $withUnit = true): string
    {
        return number_format($this->kj(), 0, '.', '') . ($withUnit ? '&nbsp;' . EnergyUnit::KJ : '');
    }
}
