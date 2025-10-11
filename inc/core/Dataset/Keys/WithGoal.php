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
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::WITH_GOAL;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return 'with_goal';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Training with goal');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return __('TG');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'This training has a goal like Garmins Pace-Goal or Garmins (custom-) targets.'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		if ($context->activity()->withGoal()) {
			return '<i class="fa fa-fw fa-flag-checkered"></i>';
		}

		return '';
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
