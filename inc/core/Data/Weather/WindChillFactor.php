<?php

/**
 * This file contains class::WindChillFactor
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use Runalyze\Activity\Pace;
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
     * Construct WindChillFactor
     */
    public function __construct(WindSpeed $windSpeed, ActivityTemperature $temperature, ?Pace $activitySpeed = null)
    {
        $this->setFrom($windSpeed, $temperature, $activitySpeed);
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
     * @throws \InvalidArgumentException
     * @see https://en.wikipedia.org/wiki/Wind_chill#North_American_and_United_Kingdom_wind_chill_index
     */
    public function setFrom(WindSpeed $windSpeed, ActivityTemperature $temperature, ?Pace $activitySpeed = null): void
    {
        if ($windSpeed->isUnknown() || $temperature->isEmpty()) {
            throw new \InvalidArgumentException('Wind speed and temperature must be known. Null value(s) given.');
        }

        $kmh = $windSpeed->inKilometerPerHour();
        $calc = $temperature->celsius();

        // #TSC: $activitySpeed->asKmPerHour() can be the string "11,7".
        // so if not numeric: replace , with . and convert
        // if there are problems, the (float) cast is robust :-)
        if ($activitySpeed instanceof \Runalyze\Activity\Pace) {
            if (is_numeric($activitySpeed->asKmPerHour())) {
                // if numeric use direct
                $actSpeed = $activitySpeed->asKmPerHour();
            } else {
                // if string try to "convert"
                $akm = str_replace(',', '.', $activitySpeed->asKmPerHour());
                $actSpeed = (float) $akm;
            }

            $kmh = $windSpeed->inKilometerPerHour() + $actSpeed;
        }

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
