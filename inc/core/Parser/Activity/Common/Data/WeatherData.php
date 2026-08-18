<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Profile\Weather\Source\WeatherSourceProfile;

class WeatherData
{
    /** @var string */
    public $Condition = '';

    /** @var null|int */
    public $InternalConditionId;

    /** @var null|int|float */
    public $Temperature;

    /** @var null|int|float [km/h] */
    public $WindSpeed;

    /** @var null|int [°] */
    public $WindDirection;

    /** @var null|int [%] */
    public $Humidity;

    /** @var null|int|float [hPa] */
    public $AirPressure;

    /**
     * @var null|int
     *
     * @see WeatherSourceProfile
     */
    public $Source;

    public function isEmpty(): bool
    {
        return (
            '' == $this->Condition &&
            null === $this->InternalConditionId &&
            null === $this->Temperature &&
            null === $this->WindSpeed &&
            null === $this->WindDirection &&
            null === $this->Humidity &&
            null === $this->AirPressure
        );
    }
}
