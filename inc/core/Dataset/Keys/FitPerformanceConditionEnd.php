<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

class FitPerformanceConditionEnd extends AbstractKey
{
	/**
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_PERFORMANCE_CONDITION_END;
	}

	/**
	 * @return string
	 */
	public function column(): string
	{
		return 'fit_performance_condition_end';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
        return __('Performance Condition').' '.__('at the end').' '.__('(by file)');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel(): string
	{
		return __('PC End');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description(): string
	{
		return __(
		    'The performance condition is an assessment, detected by some Garmin devices, '.
			'of your ability to perform compared to your average fitness level.'
		);
	}

	/**
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		return $context->dataview()->fitPerformanceConditionEnd();
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
