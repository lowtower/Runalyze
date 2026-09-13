<?php

declare(strict_types=1);

/**
 * This file contains class::FitTrainingEffectBenefit
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: FitTrainingEffectBenefit
 *
 * @package Runalyze\Dataset\Keys
 */
class FitTrainingEffectBenefit extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::FIT_TRAINING_EFFECT_BENEFIT;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'fit_training_effect_benefit';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Training effect benefit');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('TEB');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Nutzen der Aktivität für das Training.'
        );
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->fitTrainingEffectBenefit();
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }
}
