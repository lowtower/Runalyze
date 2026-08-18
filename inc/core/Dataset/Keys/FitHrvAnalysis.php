<?php

/**
 * This file contains class::FitHrvAnalysis
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: FitHrvAnalysis
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitHrvAnalysis extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::FIT_HRV_ANALYSIS;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'fit_hrv_analysis';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('HRV score').' '.__('(by file)');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('HRV');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'Garmin calculates some HRV score within the first 12 minutes of '.
			'every activity. The value - probably the recovery time in minutes - '.
			'is display as a colored marker, green for low values and red for high values. '.
			'Garmin shows this value as recovery status.'
		);
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->dataview()
		    ->fitHRVscore();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::AVG;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
