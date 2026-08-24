<?php

/**
 * This file contains class::FitPerformanceCondition
 * @package Runalyze\Dataset\Keys
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitPerformanceCondition
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitPerformanceCondition extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_PERFORMANCE_CONDITION;
	}

    /**
     * Database key
     */
    public function column(): array
	{
        return ['fit_performance_condition', 'fit_performance_condition_end'];
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Performance Condition') . ' ' . __('(by file)');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('PC');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'The performance condition is an assessment, detected by some Garmin devices, ' .
			'of your ability to perform compared to your average fitness level.'
		);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->fitPerformanceCondition();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
