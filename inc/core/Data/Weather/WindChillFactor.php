<?php

/**
 * This file contains class::WindChillFactor
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use Runalyze\Activity\Temperature as ActivityTemperature;
use Runalyze\Activity\ValueInterface;

/**
 * Wind chill
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Data\Weather
 */
class WindChillFactor implements ValueInterface
{
    protected ActivityTemperature $AdjustedTemperature;

    /**
     * @param float $activitySpeedKmh Activity speed in km/h
     */
    public function __construct(WindSpeed $windSpeed, ActivityTemperature $temperature, float $activitySpeedKmh = 0.0)
    {
        $this->setFrom($windSpeed, $temperature, $activitySpeedKmh);
    }

    /**
     * Set adjusted temperature
     *
     * Attention: No calculation will be done!
     * @param float|string|null $temperature [°C]
     */
    public function set($temperature): self
    {
        $this->AdjustedTemperature->set($temperature);

        return $this;
    }

    /**
     * Calculate and set adjusted temperature
     * @param float $activitySpeedKmh Activity speed in km/h
     * @throws \InvalidArgumentException
     * @see https://en.wikipedia.org/wiki/Wind_chill#North_American_and_United_Kingdom_wind_chill_index
     */
    public function setFrom(WindSpeed $windSpeed, ActivityTemperature $temperature, float $activitySpeedKmh = 0.0): void
    {
        if ($windSpeed->isUnknown() || $temperature->isEmpty()) {
            throw new \InvalidArgumentException('Wind speed and temperature must be known. Null value(s) given.');
        }

        $kmh = $windSpeed->inKilometerPerHour() + $activitySpeedKmh;
        $calc = $temperature->celsius();

        if ($kmh >= 5) {
            $calc = 13.12 + 0.6215 * $temperature->celsius() - 11.37 * $kmh ** 0.16 + 0.3965 * $temperature->celsius() * $kmh ** 0.16;
        }

        $this->AdjustedTemperature = clone $temperature;
        $this->AdjustedTemperature->set($calc);
    }

    /**
     * Label for value
     */
    public function label(): string
    {
        return __('Wind chill factor');
    }

    public function unit(): string
    {
        return $this->AdjustedTemperature->unit();
    }

    /**
     * @return float|null [°C]
     */
    public function value(): ?float
    {
        return $this->AdjustedTemperature->value();
    }

    /**
     * Uses the same unit as the initially given temperature object.
     */
    public function valueInPreferredUnit(): ?float
    {
        return $this->AdjustedTemperature->valueInPreferredUnit();
    }

    public function adjustedTemperature(): ActivityTemperature
    {
        return $this->AdjustedTemperature;
    }

    /**
     * Format temperature as string
     * @param bool $withUnit [optional] show unit?
     * @param bool|int $decimals [optional] number of decimals
     */
    public function string(bool $withUnit = true, $decimals = 0): string
    {
        return $this->AdjustedTemperature->string($withUnit, $decimals);
    }
}
