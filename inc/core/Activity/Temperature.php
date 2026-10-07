<?php

/**
 * This file contains class::Temperature
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\TemperatureUnit;

/**
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Activity
 */
class Temperature implements ValueInterface
{
    public static int $DefaultDecimals = 0;

    /**
     * Temperature [°C]
     */
    protected ?float $Temperature = null;

    protected TemperatureUnit $PreferredUnit;

    /**
     * @param float|null $temperature [°C]
     */
    public static function format(?float $temperature, bool $withUnit = true, int|bool $decimals = false): string
    {
        return new self($temperature)->string($withUnit, $decimals);
    }

    /**
     * @param float|null $temperature [°C]
     */
    public function __construct(?float $temperature = null, ?TemperatureUnit $preferredUnit = null)
    {
        $this->PreferredUnit = $preferredUnit ?? Configuration::General()->temperatureUnit();

        $this->set($temperature);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Temperature');
    }

    public function unit(): string
    {
        return $this->PreferredUnit->unit();
    }

    /**
     * @param float|string|null $temperature [°C]
     */
    public function set(mixed $temperature): self
    {
        if ($temperature === null || $temperature === '') {
            $this->Temperature = null;
        } else {
            $this->Temperature = round((float) str_replace(',', '.', $temperature), 2);
        }

        return $this;
    }

    /**
     * @param float|string|null $temperature [°F]
     */
    public function setFahrenheit(float|string|null $temperature): self
    {
        if ($temperature === null || $temperature === '') {
            $this->Temperature = null;
        } else {
            $this->Temperature = round(((float) str_replace(',', '.', $temperature) - 32) / 1.8, 2);
        }

        return $this;
    }

    /**
     * @param float|string|null $temperature [mixed unit]
     */
    public function setInPreferredUnit(float|string|null $temperature): self
    {
        if ($this->PreferredUnit->isFahrenheit()) {
            $this->setFahrenheit($temperature);
        } else {
            $this->set($temperature);
        }

        return $this;
    }

    public function string(bool $withUnit = true, int|bool $decimals = false): string
    {
        if ($this->PreferredUnit->isFahrenheit()) {
            return $this->stringFahrenheit($withUnit, $decimals);
        }

        return $this->stringCelsius($withUnit, $decimals);
    }

    /**
     * @return float|null [°C]
     */
    public function value(): ?float
    {
        return $this->Temperature;
    }

    /**
     * @return float|null [°C]
     */
    public function celsius(): ?float
    {
        return $this->Temperature;
    }

    /**
     * @return float|null [°F]
     */
    public function fahrenheit(): ?float
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->Temperature * 1.8 + 32;
    }

    public function isEmpty(): bool
    {
        return $this->Temperature === null;
    }

    /**
     * @return float|null [mixed unit]
     */
    public function valueInPreferredUnit(): ?float
    {
        if ($this->PreferredUnit->isFahrenheit()) {
            return $this->fahrenheit();
        }

        return $this->celsius();
    }

    public function stringCelsius(bool $withUnit = true, int|bool $decimals = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->Temperature, $decimals, '.', '') . ($withUnit ? '&nbsp;' . TemperatureUnit::CELSIUS : '');
    }

    public function stringFahrenheit(bool $withUnit = true, int|bool $decimals = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $decimals = ($decimals === false) ? self::$DefaultDecimals : $decimals;

        return number_format($this->fahrenheit(), $decimals, '.', '') . ($withUnit ? '&nbsp;' . TemperatureUnit::FAHRENHEIT : '');
    }
}
