<?php

declare(strict_types=1);

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

class FitPerformanceConditionStart extends AbstractKey
{
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_PERFORMANCE_CONDITION_START;
	}

	public function column(): string
	{
		return 'fit_performance_condition';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Performance Condition').' '.__('at start').' '.__('(by file)');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('PC start');
	}

    /**
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
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->dataview()
		    ->fitPerformanceConditionStart();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
