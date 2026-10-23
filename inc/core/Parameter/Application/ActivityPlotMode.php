<?php

declare(strict_types=1);

/**
 * This file contains class::ActivityPlotMode
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

/**
 * Activity plot mode
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class ActivityPlotMode extends \Runalyze\Parameter\Select
{
    /**
     * All seperated
     */
    public const string SEPERATED = 'all';

    /**
     * Pace + Heart rate
     */
    public const string PACE_HR = 'pacepulse';

    /**
     * Pace + Heart rate + Elevation
     */
    public const string PACE_HR_ELEVATION = 'collection';

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct(self::SEPERATED, [
            'options' => [
                self::SEPERATED => __('all separated'),
                self::PACE_HR => __('Pace / Heart rate'),
                self::PACE_HR_ELEVATION => __('Pace / Heart rate / Elevation'),
            ],
        ]);
    }

    public function showSeperated(): bool
    {
        return $this->value() == self::SEPERATED;
    }

    /**
     * Show pace and heart rate
     */
    public function showPaceAndHR(): bool
    {
        return $this->value() == self::PACE_HR;
    }

    /**
     * Show pace and heart rate and elevation
     */
    public function showCollection(): bool
    {
        return $this->value() == self::PACE_HR_ELEVATION;
    }
}
