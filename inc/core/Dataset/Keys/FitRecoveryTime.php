<?php

declare(strict_types=1);

/**
 * This file contains class::FitRecoveryTime
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitRecoveryTime
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitRecoveryTime extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::FIT_RECOVERY_TIME;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'fit_recovery_time';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Recovery time') . ' ' . __('(by file)');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('Recovery time');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Garmin calculates a suggested recovery time based on your ' .
            'heart rate variability right after the end of your activity.'
        );
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->fitRecoveryTime();
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }
}
