<?php

/**
 * This file contains class::AirPressure
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: AirPressure
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class AirPressure extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::AIR_PRESSURE;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'pressure';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Air pressure');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Pressure');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if (
			! $context->activity()
			    ->weather()
			    ->pressure()
			    ->isUnknown() &&
			(! $context->hasSport() || $context->sport()->isOutside())
		) {
			return $context->activity()
			    ->weather()
			    ->pressure()
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
