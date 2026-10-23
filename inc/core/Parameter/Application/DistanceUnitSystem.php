<?php

declare(strict_types=1);

/**
 * This file contains class::DistanceUnitSystem
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Elevation;
use Runalyze\Activity\StrideLength;

/**
 * System for distance units (metric / imperial)
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\Parameter\Application
 */
class DistanceUnitSystem extends \Runalyze\Parameter\Select
{
    /**
     * Metric system (km, m, cm)
     */
    public const string METRIC = 'metric';

    /**
     * Imperial system (miles, feet, yards)
     */
    public const string IMPERIAL = 'imperial';

    /**
     * Factor: km => miles
     */
    public const float MILE_MULTIPLIER = 0.621371192;

    /**
     * Factor: km => yards
     */
    public const float YARD_MULTIPLIER = 1093.6133;

    /**
     * Factor: km => feet
     */
    public const float FEET_MULTIPLIER = 3280.84;

    /**
     * Unit: km
     */
    public const string KM = 'km';

    /**
     * Unit: meter
     */
    public const string METER = 'm';

    /**
     * Unit: cm
     */
    public const string CM = 'cm';

    /**
     * Unit: miles
     */
    public const string MILES = 'mi';

    /**
     * Unit: yards
     */
    public const string YARDS = 'yd';

    /**
     * Unit: feet
     */
    public const string FEET = 'ft';

    /**
     * Construct
     * @param string $default from class enum
     */
    public function __construct(string $default = self::METRIC)
    {
        parent::__construct($default, [
            'options' => [
                self::METRIC => __('Metric units'),
                self::IMPERIAL => __('Imperial units'),
            ],
        ]);
    }

    public function isMetric(): bool
    {
        return $this->value() == self::METRIC;
    }

    public function isImperial(): bool
    {
        return $this->value() == self::IMPERIAL;
    }

    public function distanceToKmFactor(): float
    {
        if ($this->isImperial()) {
            return 1 / self::MILE_MULTIPLIER;
        }

        return 1;
    }

    public function distanceToPreferredUnitFactor(): float
    {
        if ($this->isImperial()) {
            return self::MILE_MULTIPLIER;
        }

        return 1;
    }

    public function distanceUnit(): string
    {
        return new Distance(0, $this)->unit();
    }

    public function elevationUnit(): string
    {
        return new Elevation(0, $this)->unit();
    }

    public function strideLengthUnit(): string
    {
        return new StrideLength(0, $this)->unit();
    }
}
