<?php

/**
 * This file contains class::HeartrateAverage
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Activity\HeartRate;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: HeartrateAverage
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class HeartrateAverage extends AbstractKey
{
	// default bound for heart-rate if no user/Athlete configuration exists
	const HR_MIN = 40;

	const HR_MAX = 220;

    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::HEARTRATE_AVG;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'pulse_avg';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('avg.') . ' ' . __('Heart rate');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('avg.') . ' ' . __('HR');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		if ($context->activity()->hrAvg() > 0) {
			return $context->dataview()
			    ->hrAvg()
			    ->string();
		}

		return '';
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

    /**
     * @codeCoverageIgnore
     */
    public function defaultCssStyle(): string
	{
		return 'font-style:italic;';
	}

    /**
     * returns a value dependend CSS class and style.
     * #TSC
     * @return array with key "class" and "style"
     */
    public function valueDependendCssStyle(Context $context)
	{
		return self::hrBarCssStyle($context->dataview()->hrAvg());
	}

    /**
     * returns the css class and style to show the HR as a bar (depending of the rest- and max-HR).
     * #TSC
     * @return array with key "class" and "style"
     */
    public static function hrBarCssStyle(?HeartRate $hr, ?string $class = 'bar gray')
	{
		$hrBpm = $hr->inBPM();
		$hrMin = $hr->getHRrest() ? $hr->getHRrest() : self::HR_MIN;
		$hrMax = $hr->getHRmax() ? $hr->getHRmax() : self::HR_MAX;

		if (isset($hr) && $hrBpm > 0) {
			$v = round(100 * ($hrBpm - $hrMin) / ($hrMax - $hrMin));
			return [
    'class' => $class,
			    'style' => '--perc: ' . $v . '%;',
];
		}

        return null;
	}
}
