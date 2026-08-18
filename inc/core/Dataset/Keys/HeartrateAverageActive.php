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
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::HEARTRATE_AVG_ACTIVE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'pulse_avg_active';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return '&#216; '.__('Heart rate').' active rounds';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel(): string
	{
		return '&#216;  '.__('HR').' act';
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		if ($context->activity()->hrAvgActive() > 0) {
			return $context->dataview()->hrAvgActive()->string();
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
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass(): string
	{
		return 'small';
	}

	/**
	 * returns a value dependend CSS class and style.
	 * #TSC
     * @param \Runalyze\Dataset\Context $context
	 * @return array with key "class" and "style"
	 */
	public function valueDependendCssStyle(Context $context)
	{
		return HeartrateAverage::hrBarCssStyle($context->dataview()->hrAvgActive());
	}
}
