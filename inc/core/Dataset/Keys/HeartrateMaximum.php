<?php

/**
 * This file contains class::HeartrateMaximum
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: HeartrateMaximum
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class HeartrateMaximum extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::HEARTRATE_MAX;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'pulse_max';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('max.').' '.__('Heart rate');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return 'max. '.__('HR');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if ($context->activity()->hrMax() > 0) {
			return $context->dataview()
			    ->hrMax()
			    ->string();
		}

		return '';
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::MAX;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}

    /**
     * returns a value dependend CSS class and style.
     * #TSC
     * @return array with key "class" and "style"
     */
    public function valueDependendCssStyle(Context $context)
	{
		return HeartrateAverage::hrBarCssStyle($context->dataview()->hrMax(), 'bar red');
	}
}
