<?php

declare(strict_types=1);

/**
 * This file contains class::FitAnaerobicTrainingEffect
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitAnaerobicTrainingEffect
 *
 * #TSC: this class contains the labels/short/description for main-data-table, and the dataview (used to select the attributes/position for main-table).
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class FitAnaerobicTrainingEffect extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::FIT_ANAEROBIC_TRAINING_EFFECT;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'fit_anaerobic_training_effect';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        // #TSC: label on the dataview-configuration screen
        return __('Anaerob Training Effect') . ' ' . __('(by file)');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        // #TSC: short-label for main-table
        return __('ATE');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Anaerobic Training Effect is an indicator between 0.0 (none) and 5.0 (overreaching) ' .
            'to rate the impact of anaerobic exercise on your body.'
        );
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->fitAnaerobicTrainingEffect();
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }
}
