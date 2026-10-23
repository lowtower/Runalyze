<?php

declare(strict_types=1);

/**
 * This file contains class::Running
 * @package Runalyze\Data\Cadence
 */

namespace Runalyze\Data\Cadence;

/**
 * Cadence for running in steps per minute
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Cadence
 */
class Running extends AbstractCadence
{
    /**
     * Factor for manipulating value
     */
    #[\Override]
    protected float $Factor = 2.0;

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Cadence (running)');
    }

    /**
     * @codeCoverageIgnore
     */
    public function unitAsString(): string
    {
        return 'spm';
    }

    /**
     * Explanation for unit
     * @codeCoverageIgnore
     */
    protected function unitExplanation(): string
    {
        return __('spm = steps per minute');
    }

    /**
     * @codeCoverageIgnore
     */
    public function formularUnit(): string
    {
        return \FormularUnit::$SPM;
    }
}
