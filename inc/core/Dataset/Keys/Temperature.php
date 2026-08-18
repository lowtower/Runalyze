<?php

/**
 * This file contains class::Temperature
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Temperature
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Temperature extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::TEMPERATURE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'temperature';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return __('Temperature');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel(): string
	{
		return __('Temp.');
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context): string
	{
		// #TSC show the temperatur in the dataset if temp is available; the original code only shows in case of "not indoor"
		if (! empty($context->activity()->weather()->temperature())) {
			return \Runalyze\Activity\Temperature::format(
			    $context->activity()->weather()->temperature()->value(),
			    true,
			    false
			);
		}

		return '';
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::AVG_WITH_ZERO;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass(): string
	{
		return 'small';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function defaultCssStyle(): string
	{
		return 'width:35px;';
	}
}
