<?php

declare(strict_types=1);

/**
 * This file contains class::Temperature
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\View\Activity;

/**
 * Plot for: Temperature
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class DistanceSeries extends ActivitySeries
{
    /**
     * @var string
     */
    public const COLOR = 'rgba(255,255,255,0)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::DISTANCE);
    }

    protected function initOptions()
    {
        $this->Label = __('Distance');
        $this->Color = self::COLOR;

        $this->UnitString = Configuration::General()->distanceUnitSystem()->distanceUnit();
        $this->UnitDecimals = 2;

        if (Configuration::General()->distanceUnitSystem()->isImperial()) {
            $this->UnitFactor = DistanceUnitSystem::MILE_MULTIPLIER;
        }

        $this->TickSize = 10;
        $this->TickDecimals = 0;

        $this->ShowAverage = false;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }
}
