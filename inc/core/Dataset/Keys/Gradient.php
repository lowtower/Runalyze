<?php

/**
 * This file contains class::Gradient
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Gradient
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class Gradient extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::GRADIENT;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): array
	{
		return array('elevation', 'distance');
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Gradient');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Grad.');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('The gradient describes the ratio of elevation gain (or less) and distance. Dividing total elevation by total distance gives this value.');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if ($context->activity()->elevation() > 0 && $context->activity()->distance() > 0) {
			return $context->dataview()
			    ->gradientInPercent();
		}

		return '';
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::NO;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
