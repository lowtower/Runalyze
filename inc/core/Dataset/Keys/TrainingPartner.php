<?php

/**
 * This file contains class::TrainingPartner
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: TrainingPartner
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class TrainingPartner extends AbstractKey
{
	/** @var int */
	const DEFAULT_CUT = 20;

	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::TRAININGPARTNER;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'partner';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return __('Training partner');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel(): string
	{
		return __('Partner');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description(): string
	{
		return sprintf(
			__('Training partner are automatically cut after %u characters.'),
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
			$context->activity()->partner()->asString(),
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
