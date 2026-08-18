<?php

/**
 * This file contains class::Wind
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\View\Icon\WindIcon;

/**
 * Dataset key: Wind
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Wind extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::WIND;
    }

    /**
     * Database key
     */
    public function column(): array
    {
        return ['wind_speed', 'wind_deg'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Wind');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        $weather = $context->activity()
            ->weather();

        if (
            (! $weather->windSpeed()->isUnknown() || ! $weather->windDegree()->isUnknown()) &&
            (! $context->hasSport() || $context->sport()->isOutside())
        ) {
            return (new WindIcon($weather->windSpeed(), $weather->windDegree()))->code();
        }

        return '';
    }
}
