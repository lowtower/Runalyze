<?php

declare(strict_types=1);

/**
 * This file contains class::AverageRespirationRate
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * #TSC Dataset key: TotalCycles
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class TotalCycles extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::TOTAL_CYCLES;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'total_cycles';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return 'Total cycles';
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('Cycles');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Wiederholungen insgesamt.'
        );
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return (string) $context->activity()
            ->totalCycles();
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }
}
