<?php

/**
 * This file contains class::WithGoal
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: WithGoal
 * #TSC
 * @package Runalyze\Dataset\Keys
 */
class WithGoal extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::WITH_GOAL;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'with_goal';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Training with goal');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('TG');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'This training has a goal like Garmins Pace-Goal or Garmins (custom-) targets.'
		);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		if ($context->activity()->withGoal()) {
			return '<i class="fa fa-fw fa-flag-checkered"></i>';
		}

		return '';
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
