<?php

/**
 * This file contains class::StrideLength
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitLoadPeak
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitLoadPeak extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_LOAD_PEAK;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'fit_load_peak';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Training load peak');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Load');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'Die Belastung eines Trainings ist ein numerischer Wert, der angibt, inwieweit sich das Training auf den Körper auswirkt.'
		);
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->activity()
		    ->fitLoadPeak();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
