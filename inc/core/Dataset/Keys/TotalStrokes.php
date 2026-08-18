<?php

/**
 * This file contains class::TotalStrokes
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: TotalStrokes
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class TotalStrokes extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::TOTAL_STROKES;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'total_strokes';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Total strokes');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('Strokes');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->activity()->totalStrokes();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::SUM;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass(): string
	{
		return 'small';
	}
}
