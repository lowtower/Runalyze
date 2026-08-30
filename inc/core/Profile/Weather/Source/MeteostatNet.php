<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

/**
 * #TSC new source of meteostat.net
 */
class MeteostatNet extends AbstractSource
{
    public function getInternalProfileEnum(): int
    {
        return WeatherSourceProfile::METEOSTAT_NET;
    }

    public function getAttributionLabel(\Symfony\Contracts\Translation\TranslatorInterface $translator): string
    {
        return 'meteostat.net';
    }

    public function getAttributionUrl(): string
    {
        return 'https://meteostat.net/';
    }
}
