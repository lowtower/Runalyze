<?php

/**
 * This file contains class::Weather
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Weather
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Weather extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::WEATHER;
    }

    /**
     * Database key
     */
    public function column(): array
    {
        return ['weatherid', 'is_night', 'temperature', 'wind_speed', 'wind_deg', 'humidity', 'pressure'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Weather');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return '';
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if (! $context->activity()->weather()->condition()->isUnknown() && ($context->hasSport() && $context->sport()->isOutside())) {
            $icon = $context->activity()
                ->weather()
                ->condition()
                ->icon();
            $weather = $context->activity()
                ->weather();

            if ($context->activity()->isNight()) {
                $icon->setAsNight();
            }

            $TooltipCode = '';

            if ($context->hasData('temperature')) {
                $TooltipCode .= __('Temperature') . ': ' . $weather->temperature()->asString() . '<br>';
            }

            if ($context->hasData('humidity')) {
                $TooltipCode .= __('Humidity') . ': ' . $weather->humidity()->string() . '<br>';
            }

            if ($context->hasData('pressure')) {
                $TooltipCode .= __('Pressure') . ': ' . $weather->pressure()->string() . '<br>';
            }

            if ($context->hasData('wind_speed')) {
                $TooltipCode .= __('Wind Speed') . ': ' . $weather->windSpeed()->string() . '<br>';
            }

            if ($context->hasData('wind_deg')) {
                $TooltipCode .= __('Wind Degree') . ': ' . $weather->windDegree()->string() . '<br>';
            }

            $icon->setTooltip($TooltipCode);
            return $icon->code();
        }

        return '';
    }
}
