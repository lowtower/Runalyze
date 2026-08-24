<?php

/**
 * This file contains class::GroundcontactBalance
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: GroundcontactBalance
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class GroundcontactBalance extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::GROUNDCONTACT_BALANCE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'groundcontact_balance';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Ground contact balance');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('GCB');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __('Ground contact time balance measures your running symmetry.');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->groundcontactBalance();
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
