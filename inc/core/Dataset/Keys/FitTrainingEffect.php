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
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_TRAINING_EFFECT;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'fit_training_effect';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		// #TSC: Add aerobic
		return __('(Aerob) Training Effect') . ' ' . __('(by file)');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('TE');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    '(Aerob) Training Effect is an indicator between 0.0 (none) and 5.0 (overreaching) ' .
			'to rate the impact of aerobic exercise on your body.'
		);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->fitTrainingEffect();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
