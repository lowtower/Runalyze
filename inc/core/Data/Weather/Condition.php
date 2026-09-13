<?php

namespace Runalyze\Data\Weather;

use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\View\Icon\Weather;
use Runalyze\View\Icon\WeatherIcon;

class Condition
{
    /**
     * @var int see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    protected int $identifier;

    /**
     * @return array
     */
    public static function completeList()
    {
        return WeatherConditionProfile::getEnum();
    }

    /**
     * @param int $identifier see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function __construct(int $identifier)
    {
        $this->set($identifier);
    }

    /**
     * @param int $identifier see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function set($identifier)
    {
        if (! in_array($identifier, self::completeList())) {
            $this->identifier = WeatherConditionProfile::UNKNOWN;
        } else {
            $this->identifier = $identifier;
        }
    }

    /**
     * @return int see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function id(): int
    {
        return $this->identifier;
    }

    public function isUnknown(): bool
    {
        return $this->identifier === WeatherConditionProfile::UNKNOWN;
    }

    public function icon(): WeatherIcon
    {
        return match ($this->identifier) {
            WeatherConditionProfile::SUNNY => new Weather\Sunny(),
            WeatherConditionProfile::FAIR => new Weather\Fair(),
            WeatherConditionProfile::CLOUDY => new Weather\Cloudy(),
            WeatherConditionProfile::FOGGY => new Weather\Foggy(),
            WeatherConditionProfile::CHANGEABLE => new Weather\Changeable(),
            WeatherConditionProfile::THUNDERSTORM => new Weather\Thunderstorm(),
            WeatherConditionProfile::RAINY => new Weather\Rainy(),
            WeatherConditionProfile::HEAVYRAIN => new Weather\Heavyrain(),
            WeatherConditionProfile::SNOWING => new Weather\Snowing(),
            WeatherConditionProfile::WINDY => new Weather\Windy(),
            default => new Weather\Unknown(),
        };
    }

    public function string(): string
    {
        return match ($this->identifier) {
            WeatherConditionProfile::SUNNY => __('sunny'),
            WeatherConditionProfile::FAIR => __('fair'),
            WeatherConditionProfile::CLOUDY => __('cloudy'),
            WeatherConditionProfile::CHANGEABLE => __('changeable'),
            WeatherConditionProfile::RAINY => __('rainy'),
            WeatherConditionProfile::SNOWING => __('snowing'),
            WeatherConditionProfile::HEAVYRAIN => __('heavy rain'),
            WeatherConditionProfile::FOGGY => __('foggy'),
            WeatherConditionProfile::THUNDERSTORM => __('thundery'),
            WeatherConditionProfile::WINDY => __('windy'),
            default => __('unknown'),
        };
    }
}
