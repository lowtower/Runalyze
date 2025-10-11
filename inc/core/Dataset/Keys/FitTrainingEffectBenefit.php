<?php
/**
 * This file contains class::FitTrainingEffectBenefit
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitTrainingEffectBenefit
 * 
 * @package Runalyze\Dataset\Keys
 */
class FitTrainingEffectBenefit extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::FIT_TRAINING_EFFECT_BENEFIT;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'fit_training_effect_benefit';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Training effect benefit');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('TEB');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'Nutzen der Aktivität für das Training.'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->dataview()->fitTrainingEffectBenefit();
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass()
	{
		return 'small';
	}
}
