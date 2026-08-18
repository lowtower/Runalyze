<?php

namespace Runalyze\Profile\Weather\Source;

use Symfony\Component\Translation\TranslatorInterface;

class OpenWeatherMap extends AbstractSource
{
	public function getInternalProfileEnum(): int
    {
        return WeatherSourceProfile::OPEN_WEATHER_MAP;
    }

    public function getAttributionLabel(TranslatorInterface $translator): string
    {
        return 'openweathermap.org';
    }

    public function getAttributionUrl(): string
    {
        return 'http://openweathermap.org/';
    }
}
