<?php

/**
 * This file contains class::Energy
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Energy
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class Energy extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::ENERGY;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'kcal';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Energy');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Energy values are taken from your device or calculated by a simple formula.');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		if ($context->activity()->energy() > 0) {
			return $context->dataview()
			    ->energy()
			    ->string();
		}

		return '';
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
