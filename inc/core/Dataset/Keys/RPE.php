<?php

declare(strict_types=1);

/**
 * This file contains class::RPE
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: RPE
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class RPE extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::RPE;
	}

	public function isInDatabase(): bool
	{
		return true;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'rpe';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('RPE');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Rating of Perceived Exertion or Borg scale, a scale devised to show perceived exertion during exercise');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
	    return $context->dataview()
	        ->rpe();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::NO;
	}
}
