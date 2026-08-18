<?php

/**
 * This file contains class::VerticalRatio
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: VerticalRatio
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class VerticalRatio extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::VERTICAL_RATIO;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'vertical_ratio';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Vertical ratio');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Vertical ratio is the ratio of vertical oscillation to stride length.');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->dataview()
		    ->verticalRatio();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::AVG;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
