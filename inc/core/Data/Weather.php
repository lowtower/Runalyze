<?php

/**
 * This file contains class::Weather
 * @package Runalyze\Data
 */

namespace Runalyze\Data;

use Runalyze\Data\Weather\Condition;
use Runalyze\Data\Weather\Humidity;
use Runalyze\Data\Weather\Pressure;
use Runalyze\Data\Weather\Sources;
use Runalyze\Data\Weather\Temperature;
use Runalyze\Data\Weather\WindDegree;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Profile\Weather\WeatherConditionProfile;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Data
 */
class Weather
{
    /**
     * Temperature
     */
    protected Temperature $Temperature;

    /**
     * Condition
     */
    protected Condition $Condition;

    /**
     * WindSpeed
     */
    protected WindSpeed $WindSpeed;

    /**
     * WindDegree
     */
    protected WindDegree $WindDegree;

    /**
     * Humidity
     */
    protected Humidity $Humidity;

    /**
     * Pressure
     */
    protected Pressure $Pressure;

    /**
     * Enum for weather source
     * @see \Runalyze\Data\Weather\Sources
     */
    protected ?int $SourceId = null;

    public function __construct(Temperature $Temperature, Condition $Condition, WindSpeed $WindSpeed, WindDegree $WindDegree, Humidity $Humidity, Pressure $Pressure)
    {
        $this->Temperature = $Temperature;
        $this->Condition = $Condition;
        $this->WindSpeed = $WindSpeed;
        $this->WindDegree = $WindDegree;
        $this->Humidity = $Humidity;
        $this->Pressure = $Pressure;
    }

    /**
     * Clone object
     */
    public function __clone()
    {
        $this->Temperature = clone $this->Temperature;
        $this->Condition = clone $this->Condition;
        $this->WindSpeed = clone $this->WindSpeed;
        $this->WindDegree = clone $this->WindDegree;
        $this->Humidity = clone $this->Humidity;
        $this->Pressure = clone $this->Pressure;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setSource(?int $sourceId)
    {
        if ($sourceId !== null && ! Sources::isValidValue($sourceId)) {
            throw new \InvalidArgumentException('Provided source must be null or defined in sources enum.');
        }

        $this->SourceId = $sourceId;
    }

    public function temperature(): Temperature
    {
        return $this->Temperature;
    }

    public function condition(): Condition
    {
        return $this->Condition;
    }

    public function windSpeed(): WindSpeed
    {
        return $this->WindSpeed;
    }

    public function windDegree(): WindDegree
    {
        return $this->WindDegree;
    }

    public function humidity(): Humidity
    {
        return $this->Humidity;
    }

    public function pressure(): Pressure
    {
        return $this->Pressure;
    }

    /**
     * @see \Runalyze\Data\Weather\Sources
     */
    public function source(): ?int
    {
        if ($this->isEmpty()) {
            $this->SourceId = null;
        }

        return $this->SourceId;
    }

    public function sourceAsString(): string
    {
        if ($this->sourceIsKnown()) {
            return Sources::stringFor($this->SourceId);
        }

        return '';
    }

    public function sourceIsKnown(): bool
    {
        return $this->SourceId !== null;
    }

    /**
     * Complete string for the weather conditions with icon, name and temperature.
     */
    public function fullString(bool $isNight = false): string
    {
        $icon = $this->Condition->icon();

        if ($isNight) {
            $icon->setAsNight();
        }

        return $icon->code() . ' ' . $this->Condition->string() . ' ' . __('at') . ' ' . $this->Temperature->asString();
    }

    /**
     * Is the weather-data empty?
     */
    public function isEmpty(): bool
    {
        return
            $this->Temperature->isUnknown() &&
            $this->Condition->isUnknown() &&
            $this->WindSpeed->isUnknown() &&
            $this->WindDegree->isUnknown() &&
            $this->Humidity->isUnknown() &&
            $this->Pressure->isUnknown()
        ;
    }

    /**
     * Clear weather
     */
    public function clear()
    {
        $this->Temperature->setTemperature(null);
        $this->Condition->set(WeatherConditionProfile::UNKNOWN);
        $this->WindSpeed->set(null);
        $this->WindDegree->set(null);
        $this->Humidity->set(null);
        $this->Pressure->set(null);
    }
}
