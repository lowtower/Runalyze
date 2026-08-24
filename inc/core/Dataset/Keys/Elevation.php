<?php

declare(strict_types=1);

/**
 * This file contains class::Elevation
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Elevation
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Elevation extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::ELEVATION;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'elevation';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Elevation');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Elev.');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Calculated value for total elevation, i.e. maximum of total ascent or descent.');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
        if ($context->activity()->elevation() > 0) {
            if ($context->activity()->id() > 0 && ! \Request::isOnSharedPage()) {
                return '<a class="window" href="activity/' . $context->activity()->id() . '/elevation-info">' . $context->dataview()->elevation()->string() . '</a>';
            }

            return $context->dataview()
                ->elevation()
                ->string();
        }

		return '';
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::SUM;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
