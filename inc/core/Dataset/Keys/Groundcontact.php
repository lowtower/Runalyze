<?php

/**
 * This file contains class::Groundcontact
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Groundcontact
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Groundcontact extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::GROUNDCONTACT;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'groundcontact';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Ground contact time');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Groundcontact');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->groundcontact();
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
