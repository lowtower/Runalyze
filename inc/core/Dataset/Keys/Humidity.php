<?php

/**
 * This file contains class::Humidity
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Humidity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Humidity extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::HUMIDITY;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'humidity';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Humidity');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if (
            ! $context->activity()
                ->weather()
                ->humidity()
                ->isUnknown() &&
            (! $context->hasSport() || $context->sport()->isOutside())
        ) {
            return $context->activity()
                ->weather()
                ->humidity()
                ->string();
        }

        return '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }
}
