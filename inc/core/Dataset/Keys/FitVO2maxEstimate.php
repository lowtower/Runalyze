<?php

declare(strict_types=1);

/**
 * This file contains class::FitVO2maxEstimate
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: FitVO2maxEstimate
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitVO2maxEstimate extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::FIT_VO2MAX_ESTIMATE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'fit_vo2max_estimate';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return 'VO<sub>2</sub>max ' . __('(by file)');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return 'VO<sub>2</sub>max ' . __('(file)');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __("Garmin's newer devices have an integrated VO2max estimation.") . ' '
            . __('This value refers to your estimated VO2max at the start of the activity.');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if ($context->isRunning()) {
            return $context->dataview()
                ->fitVO2maxEstimate();
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
}
