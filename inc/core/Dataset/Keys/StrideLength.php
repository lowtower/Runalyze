<?php

/**
 * This file contains class::StrideLength
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: StrideLength
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class StrideLength extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::STRIDE_LENGTH;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'stride_length';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Stride length');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if ($context->activity()->strideLength() > 0) {
			return $context->dataview()
			    ->strideLength()
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
}
