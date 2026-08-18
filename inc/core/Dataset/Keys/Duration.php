<?php

/**
 * This file contains class::Duration
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Duration
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Duration extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::DURATION;
	}

    /**
     * Is this key always shown?
     * @codeCoverageIgnore
     */
    public function mustBeShown(): bool
	{
		return true;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 's';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Duration');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->duration()
		    ->string();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::SUM;
	}
}
