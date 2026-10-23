<?php

/**
 * This file contains class::HeatIndex
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: HeatIndex
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class HeatIndex extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::HEAT_INDEX;
    }

    /**
     * Database key
     */
    public function column(): array
    {
        return ['temperature', 'humidity'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Heat index');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('HI');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'The heat index is a measure of how hot it really feels when relative humidity is factored with the actual air temperature.'
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
                ->isUnknown()
            && ! $context->activity()
                ->weather()
                ->humidity()
                ->isUnknown()
            && (! $context->hasSport() || $context->sport()->isOutside())
        ) {
            return $context->dataview()
                ->heatIndex()
                ->getIcon();
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
