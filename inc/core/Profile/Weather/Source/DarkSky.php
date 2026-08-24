<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

use Symfony\Component\Translation\TranslatorInterface;

class DarkSky extends AbstractSource
{
    public function getInternalProfileEnum(): int
    {
        return WeatherSourceProfile::DARK_SKY;
    }

    public function getAttributionLabel(TranslatorInterface $translator): string
    {
        return 'Powered by Dark Sky';
    }

    public function getAttributionUrl(): string
    {
        return 'https://darksky.net/poweredby/';
    }
}
