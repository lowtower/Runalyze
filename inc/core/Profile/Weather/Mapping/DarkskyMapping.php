<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Mapping;

use Runalyze\Profile\Mapping\ToInternalMappingInterface;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class DarkskyMapping implements ToInternalMappingInterface
{
    /**
     * @see https://darksky.net/dev/docs/response
     *
     * @param int|string $value
     */
    public function toInternal($value): int
    {
        return match ($value) {
            'clear-day', 'clear-night' => WeatherConditionProfile::FAIR,
            'wind' => WeatherConditionProfile::WINDY,
            'fog' => WeatherConditionProfile::FOGGY,
            'partly-cloudy-night', 'partly-cloudy-day' => WeatherConditionProfile::CHANGEABLE,
            'cloudy' => WeatherConditionProfile::CLOUDY,
            'rain' => WeatherConditionProfile::RAINY,
            'snow', 'sleet' => WeatherConditionProfile::SNOWING,
            default => WeatherConditionProfile::UNKNOWN,
        };
    }
}
