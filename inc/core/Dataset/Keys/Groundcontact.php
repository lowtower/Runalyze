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
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::GROUNDCONTACT;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'groundcontact';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return __('Ground contact time');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel(): string
	{
		return __('Groundcontact');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->dataview()->groundcontact();
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
}
