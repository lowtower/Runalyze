<?php

/**
 * This file contains class::Temperature
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

/**
 * @deprecated since 3.1.0
 * @author Hannes Christiansen
 * @package Runalyze\Data\Weather
 */
class Temperature
{
    /**
     * @var int
     */
    public const CELSIUS = 0;

    /**
     * @var int
     */
    public const FAHRENHEIT = 1;

    /**
     * @var int
     */
    public const KELVIN = 2;

    /**
     * Unit
     */
    protected int $unit;

    /**
     * Temperature in celsius
     */
    protected ?float $inCelsius = null;

    public function __construct(?float $value = null, ?int $unit = self::CELSIUS)
    {
        $this->setTemperature($value, $unit);
    }

    public function setTemperature(?float $value, ?int $unit = null)
    {
        if ($unit !== null) {
            $this->unit = $unit;
        }

        $this->inCelsius = $this->toCelsiusFrom($value, $this->unit);
    }

    /**
     * To celsius
     */
    protected function toCelsiusFrom(?float $value, ?int $unit): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return match ($unit) {
            self::FAHRENHEIT => ($value - 32) / 1.8,
            self::KELVIN => $value - 273.15,
            default => $value,
        };
    }

    /**
     * From celsius
     */
    protected function fromCelsiusTo(?float $value, ?int $unit): ?float
    {
        if ($value === null) {
            return null;
        }

        return match ($unit) {
            self::FAHRENHEIT => $value * 1.8 + 32,
            self::KELVIN => $value + 273.15,
            default => $value,
        };
    }

    /**
     * Set unit to celsius
     */
    public function toCelsius()
    {
        $this->unit = self::CELSIUS;
    }

    /**
     * Set unit to fahrenheit
     */
    public function toFahrenheit()
    {
        $this->unit = self::FAHRENHEIT;
    }

    /**
     * Set unit to kelvin
     */
    public function toKelvin()
    {
        $this->unit = self::KELVIN;
    }

    /**
     * @return null|float [°F]
     */
    public function inFahrenheit(): ?float
    {
        return $this->fromCelsiusTo($this->inCelsius, self::FAHRENHEIT);
    }

    /**
     * Temperature unknown?
     */
    public function isUnknown(): bool
    {
        return $this->inCelsius === null;
    }

    public function value(): ?float
    {
        return $this->fromCelsiusTo($this->inCelsius, $this->unit);
    }

    /**
     * As string
     */
    public function asString(): string
    {
        return $this->asStringWithoutUnit() . '&nbsp;' . $this->unit();
    }

    /**
     * As string without unit
     * @param string $stringForUnknown [optional]
     */
    public function asStringWithoutUnit(string $stringForUnknown = '?'): string
    {
        if ($this->isUnknown()) {
            return $stringForUnknown;
        }

        return (string) round($this->value());
    }

    public function unit(): string
    {
        return match ($this->unit) {
            self::CELSIUS => '&deg;C',
            self::FAHRENHEIT => '&deg;F',
            self::KELVIN => 'K',
            default => '',
        };
    }
}
