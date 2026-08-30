<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

class OpenWeatherMap extends AbstractSource
{
    public function getInternalProfileEnum(): int
    {
        return WeatherSourceProfile::OPEN_WEATHER_MAP;
    }

    public function getAttributionLabel(\Symfony\Contracts\Translation\TranslatorInterface $translator): string
    {
        return 'openweathermap.org';
    }

    public function getAttributionUrl(): string
    {
        return 'http://openweathermap.org/';
    }
}
