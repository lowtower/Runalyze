<?php

/**
 * This file contains class::HeartrateAverage
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: HeartrateAverageActive
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class HeartrateAverageActive extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::HEARTRATE_AVG_ACTIVE;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'pulse_avg_active';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return '&#216; '.__('Heart rate').' active rounds';
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return '&#216;  '.__('HR').' act';
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		if ($context->activity()->hrAvgActive() > 0) {
			return $context->dataview()
			    ->hrAvgActive()
			    ->string();
		}

		return '';
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

    /**
     * returns a value dependend CSS class and style.
     * #TSC
     * @return array with key "class" and "style"
     */
    public function valueDependendCssStyle(Context $context)
	{
		return HeartrateAverage::hrBarCssStyle($context->dataview()->hrAvgActive());
	}
}
