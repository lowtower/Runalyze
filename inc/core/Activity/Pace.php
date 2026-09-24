<?php

/**
 * This file contains class::Pace
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Activity\PaceUnit\AbstractUnit;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Parameter\Application\PaceUnit as PaceUnitOption;

/**
 * Different pace types/units
 *
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Activity
 */
class Pace
{
    /**
     * Default speed (km/h)
     * @var int
     */
    public const STANDARD = PaceUnitOption::KM_PER_H;

    protected AbstractUnit $UnitObject;

    /**
     * @param float $Time [s]
     * @param float $Distance [km]
     */
    public function __construct(
        protected float $Time,
        protected float $Distance = 1.0,
        int $legacyPaceUnitEnum = self::STANDARD
    ) {
        $this->setLegacyUnitEnum($legacyPaceUnitEnum);
    }

    /**
     * @param string $string see Duration::fromString()
     */
    public function fromMinPerKm(string $string): void
    {
        $Duration = new Duration($string);

        $this->setTime($Duration->seconds())
            ->setDistance(1);
    }

    /**
     * @param float $time [s]
     */
    public function setTime(float $time): self
    {
        $this->Time = $time;

        return $this;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance): self
    {
        $this->Distance = $distance;

        return $this;
    }

    public function unit(): AbstractUnit
    {
        return $this->UnitObject;
    }

    /**
     * Enum for pace unit
     *
     * This is for example what is saved in sports configuration.
     */
    public function unitEnum(): int
    {
        return $this->UnitObject->unit();
    }

    public function setUnit(AbstractUnit $Unit): void
    {
        $this->UnitObject = $Unit;
    }

    public function setLegacyUnitEnum(int $legacyPaceUnitEnum): void
    {
        $Option = new PaceUnitOption();
        $Option->set($legacyPaceUnitEnum);

        $this->UnitObject = $Option->object();
    }

    public function setNewUnitEnum(int $unitEnum): void
    {
        $this->UnitObject = (new LegacyUnitConverter())->getLegacyPaceUnit($unitEnum);
    }

    public function setUnitEnum(int $unitEnum): void
    {
        // TODO: remove
    }

    public function isEmpty(): bool
    {
        return $this->Distance <= 0.0 || $this->Time <= 0.0;
    }

    public function secondsPerKm(): int
    {
        return ($this->Distance > 0.0) ? (int) round($this->Time / $this->Distance) : 0;
    }

    /**
     * @return string e.g. '12,5', '4:51'
     */
    public function value(): string
    {
        return $this->UnitObject->format($this->secondsPerKm());
    }

    public function valueWithAppendix(): string
    {
        return $this->value() . $this->appendix();
    }

    public function appendix(): string
    {
        return $this->UnitObject->appendix();
    }

    public function asUnitEnum(string $UnitEnum): string
    {
        $Option = new PaceUnitOption();
        $Option->set((int) $UnitEnum);

        return $this->asUnit($Option->object());
    }

    public function asUnit(AbstractUnit $Unit): string
    {
        return $Unit->format($this->secondsPerKm());
    }

    public function asKmPerHour(): string
    {
        return $this->asUnit(new PaceUnit\KmPerHour());
    }

    public function asMinPerKm(): string
    {
        return $this->asUnit(new PaceUnit\MinPerKilometer());
    }

    /**
     * Compare
     * Both pace objects must have the same unit and the unit must be comparable.
     * @throws \InvalidArgumentException
     */
    public function compareTo(self $other, bool $raw = false): string
    {
        if ($this->unitEnum() !== $other->unitEnum()) {
            throw new \InvalidArgumentException('Pace objects must have the same unit.');
        }

        if ($this->secondsPerKm() === 0 || $other->secondsPerKm() === 0) {
            return '';
        }

        $comparisonInSecondsPerKm = $this->UnitObject->compare($this->secondsPerKm(), $other->secondsPerKm());
        $isPositive = ($comparisonInSecondsPerKm >= 0.0);

        if ($comparisonInSecondsPerKm === 0.0 && $this->UnitObject->isTimeFormat()) {
            return $this->formatComparison('0:00', $isPositive, $raw);
        }

        return $this->formatComparison($this->UnitObject->format(abs($comparisonInSecondsPerKm)), $isPositive, $raw);
    }

    /**
     * @param string $string e.g. '0:27' or '1,4'
     */
    protected function formatComparison(string $string, bool $isPositive, bool $raw = false): string
    {
        $class = ($isPositive) ? 'plus' : 'minus';
        $sign = ($isPositive) ? '+' : '-';

        if ($raw) {
            return $sign . $string;
        }

        /**
         * @codeCoverageIgnore
         */
        return '<span class="' . $class . '">' . $sign . $string . $this->appendix() . '</span>';
    }
}
