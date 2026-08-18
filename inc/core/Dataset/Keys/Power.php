<?php

/**
 * This file contains class::Power
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Power
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Power extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::POWER;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'power';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Power');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->power();
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
