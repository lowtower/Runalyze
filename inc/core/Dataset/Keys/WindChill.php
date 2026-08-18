<?php

/**
 * This file contains class::WindChill
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: WindChill
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class WindChill extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::WIND_CHILL;
    }

    /**
     * Database key
     */
    public function column(): array
    {
        return ['temperature', 'wind_speed', 'distance', 's'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Windchill');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Windchill is the perceived air temperature felt by the body on exposed skin due to the flow of air.'
        );
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if (
            ! $context->activity()
                ->weather()
                ->temperature()
                ->isUnknown() &&
            ! $context->activity()
                ->weather()
                ->windSpeed()
                ->isUnknown() &&
            (! $context->hasSport() || $context->sport()->isOutside())
        ) {
            return $context->dataview()
                ->windChillFactor()
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
