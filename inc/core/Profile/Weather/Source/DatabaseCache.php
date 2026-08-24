<?php

declare(strict_types=1);

namespace Runalyze\Profile\Weather\Source;

use Symfony\Component\Translation\TranslatorInterface;

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

    public function getAttributionLabel(TranslatorInterface $translator)
    {
        return $translator->trans('internal database');
    }
}
