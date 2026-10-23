<?php

declare(strict_types=1);

/**
 * This file contains class::HeartRateUnit
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

/**
 * Heart rate unit
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class HeartRateUnit extends \Runalyze\Parameter\Select
{
    /**
     * BPM
     */
    public const string BPM = 'bpm';

    /**
     * % HRmax
     */
    public const string HRMAX = 'hfmax';

    /**
     * % HRreserve
     */
    public const string HRRESERVE = 'hfres';

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct(self::HRMAX, [
            'options' => [
                self::BPM => __('absolute value'),
                self::HRMAX => __('&#37; HRmax'),
                self::HRRESERVE => __('&#37; HRreserve'),
            ],
        ]);
    }

    /**
     * Is bpm?
     */
    public function isBPM(): bool
    {
        return $this->value() == self::BPM;
    }

    /**
     * Is HRmax?
     */
    public function isHRmax(): bool
    {
        return $this->value() == self::HRMAX;
    }

    /**
     * Is HRreserve?
     */
    public function isHRreserve(): bool
    {
        return $this->value() == self::HRRESERVE;
    }
}
