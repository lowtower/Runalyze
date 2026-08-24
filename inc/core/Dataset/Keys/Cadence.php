<?php

declare(strict_types=1);

/**
 * This file contains class::Cadence
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Cadence
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Cadence extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::CADENCE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'cadence';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Cadence');
    }

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
    {
        if ($context->activity()->cadence() > 0) {
            return $context->dataview()
                ->cadence()
                ->asString();
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
