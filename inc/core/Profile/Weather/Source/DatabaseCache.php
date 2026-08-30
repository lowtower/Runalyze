<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

class DatabaseCache extends AbstractSource
{
    public function getInternalProfileEnum(): int
    {
        return WeatherSourceProfile::DATABASE_CACHE;
    }

    public function requiresAttribution(): bool
    {
        return false;
    }

    public function getAttributionLabel(\Symfony\Contracts\Translation\TranslatorInterface $translator)
    {
        return $translator->trans('internal database');
    }
}
