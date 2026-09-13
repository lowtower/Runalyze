<?php

namespace Runalyze\Profile\Weather\Mapping;

use Runalyze\Profile\Mapping\ToInternalMappingInterface;
use Runalyze\Profile\Weather\WeatherConditionProfile;

/**
 * This mapping was originally used to translate strings from RunningAHEAD backup
 * but can be used for English texts in general without problems.
 */
class EnglishTextMapping implements ToInternalMappingInterface
{
    /**
     * @param int|string $value
     */
    public function toInternal($value): int
    {
        return match (strtolower($value)) {
            'mostly sunny', 'sunny', 'clear' => WeatherConditionProfile::SUNNY,
            'partly sunny', 'partly cloudy' => WeatherConditionProfile::FAIR,
            'overcast', 'mostly cloudy', 'cloudy' => WeatherConditionProfile::CLOUDY,
            'fog' => WeatherConditionProfile::FOGGY,
            'mist', 'chance of rain', 'drizzle' => WeatherConditionProfile::CHANGEABLE,
            'rain', 'light rain', 'rain and snow', 'freezing drizzle', 'sleet' => WeatherConditionProfile::RAINY,
            'scattered showers', 'showers' => WeatherConditionProfile::HEAVYRAIN,
            'storm', 'windy' => WeatherConditionProfile::WINDY,
            'chance of tstorm', 'scattered thunderstorms', 'thunderstorm' => WeatherConditionProfile::THUNDERSTORM,
            'haze', 'flurries', 'icy', 'snow', 'light snow', 'chance of snow', 'scattered snow showers' => WeatherConditionProfile::SNOWING,
            default => WeatherConditionProfile::UNKNOWN,
        };
    }
}
