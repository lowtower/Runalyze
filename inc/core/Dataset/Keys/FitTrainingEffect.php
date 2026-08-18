<?php

/**
 * This file contains class::FitTrainingEffect
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitTrainingEffect
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitTrainingEffect extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_TRAINING_EFFECT;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return 'fit_training_effect';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		// #TSC: Add aerobic
		return __('(Aerob) Training Effect').' '.__('(by file)');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('TE');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'(Aerob) Training Effect is an indicator between 0.0 (none) and 5.0 (overreaching) '.
			'to rate the impact of aerobic exercise on your body.'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->dataview()->fitTrainingEffect();
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
