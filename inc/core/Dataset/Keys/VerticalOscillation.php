<?php

declare(strict_types=1);

/**
 * This file contains class::VerticalOscillation
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: VerticalOscillation
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class VerticalOscillation extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::VERTICAL_OSCILLATION;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'vertical_oscillation';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Vertical oscillation');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __('Vertical oscillation is your vertical motion of your torso measured in centimeters');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->verticalOscillation();
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
