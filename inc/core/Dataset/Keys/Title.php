<?php

/**
 * This file contains class::Title
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Title
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Title extends AbstractKey
{
	/** @var int */
	const DEFAULT_CUT = 20;

	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::TITLE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'title';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return __('Title');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description(): string
	{
		return sprintf(
			__('Titles are automatically cut after %u characters.'),
			self::DEFAULT_CUT
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return \Helper::Cut(
			$context->activity()->title(),
			20
		);
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass(): string
	{
		return 'small l';
	}
}
