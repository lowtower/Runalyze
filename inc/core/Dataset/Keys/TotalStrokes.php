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
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::TOTAL_STROKES;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'total_strokes';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Total strokes');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Strokes');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->activity()
		    ->totalStrokes();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::SUM;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
