<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class WeatherData
{
    public string $Condition = '';

    public ?int $InternalConditionId = null;

    /**
     * @var null|int|float
     */
    public $Temperature;

    /**
     * @var null|int|float [km/h]
     */
    public $WindSpeed;

    /**
     * @var null|int [°]
     */
    public ?int $WindDirection = null;

    /**
     * @var null|int [%]
     */
    public ?int $Humidity = null;

    /**
     * @var null|int|float [hPa]
     */
    public $AirPressure;

    /**
     * @see WeatherSourceProfile
     */
    public ?int $Source = null;

    public function isEmpty(): bool
    {
        return
            $this->Condition === ''
            && $this->InternalConditionId === null
            && $this->Temperature === null
            && $this->WindSpeed === null
            && $this->WindDirection === null
            && $this->Humidity === null
            && $this->AirPressure === null
        ;
    }

    public function hasNoUsableData(): bool
    {
        return
            ($this->InternalConditionId === null || $this->InternalConditionId === WeatherConditionProfile::UNKNOWN)
            && $this->Temperature === null
            && $this->WindSpeed === null
            && $this->WindDirection === null
            && $this->Humidity === null
            && $this->AirPressure === null
        ;
    }
}
