<?php

namespace Runalyze\Data\Weather;

use Runalyze\Activity;
use Runalyze\Activity\Temperature;
use Runalyze\Activity\ValueInterface;
use Runalyze\Data\Weather;

class HeatIndex implements ValueInterface
{
    protected Temperature $HeatIndexTemperature;

    public function __construct(?Temperature $temperature = null, ?Humidity $humidity = null)
    {
        if ($temperature instanceof Temperature && $humidity instanceof Humidity) {
            $this->setFrom($temperature, $humidity);
        } else {
            $this->setUnknown();
        }
    }

    /**
     * @param null|float|Temperature $heatIndex floats are treated as temperature in config's unit
     *
     * @throws \InvalidArgumentException
     */
    public function set($heatIndex): self
    {
        if ($heatIndex === null) {
            $this->setUnknown();
        } elseif ($heatIndex instanceof Temperature) {
            $this->HeatIndexTemperature = clone $heatIndex;
        } elseif (is_numeric($heatIndex)) {
            $this->HeatIndexTemperature = new Temperature($heatIndex);
        } else {
            throw new \InvalidArgumentException("Can't handle given argument, must be null, numerical or an instance of Temperature.");
        }

        return $this;
    }

    public function setFromWeather(Weather $weather): void
    {
        $this->setFrom(
            new Activity\Temperature($weather->temperature()->value()),
            $weather->humidity()
        );
    }

    public function setFrom(Temperature $temperature, Humidity $humidity): void
    {
        if ($temperature->value() !== null && ! $humidity->isUnknown()) {
            $this->HeatIndexTemperature = clone $temperature;
            $this->HeatIndexTemperature->setFahrenheit(
                $this->calculateHeatIndexFor($temperature->fahrenheit(), $humidity->value())
            );
        } else {
            $this->setUnknown();
        }
    }

    public function setUnknown(): void
    {
        $this->HeatIndexTemperature = new Temperature();
    }

    /**
     * Calculate heat index
     *
     * Rothfusz regression is used in general, Steadman's simpler formula for
     * temperatures below 80°F.
     *
     * @see http://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
     *
     * @param float $temperatureInFahrenheit [°F]
     * @param int $humidity [0 .. 100]
     * @return float [°F]
     */
    protected function calculateHeatIndexFor(float $temperatureInFahrenheit, int $humidity): float
    {
        $t = $temperatureInFahrenheit;
        $h = $humidity;

        if ($t < 80) {
            return 1.1 * $t - 10.3 + 0.047 * $h;
        }

        return -42.379 +
            (2.04901523 * $t) +
            (10.14333127 * $h) +
            (-0.22475541 * $t * $h) +
            (-0.00683783 * $t * $t) +
            (-0.05481717 * $h * $h) +
            (0.00122874 * $t * $t * $h) +
            (0.00085282 * $t * $h * $h) +
            (-0.00000199 * $t * $t * $h * $h);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Heat index');
    }

    /**
     * @return null|float [°C|°F|K] temperature unit depends on original object
     */
    public function value(): ?float
    {
        return $this->HeatIndexTemperature->valueInPreferredUnit();
    }

    public function isUnknown(): bool
    {
        return $this->HeatIndexTemperature->isEmpty();
    }

    public function unit(): string
    {
        return $this->HeatIndexTemperature->unit();
    }

    public function string(bool $withUnit = true): string
    {
        return $this->HeatIndexTemperature->string($withUnit);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEffectOfHeatIndex(): string
    {
        $level = HeatIndexEffect::levelFor($this->HeatIndexTemperature->fahrenheit());

        return HeatIndexEffect::label($level);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getIcon(): string
    {
        $level = HeatIndexEffect::levelFor($this->HeatIndexTemperature->fahrenheit());

        return HeatIndexEffect::icon($level);
    }
}
