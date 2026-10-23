<?php

declare(strict_types=1);

/**
 * This file contains class::Thb
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: thb
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\View\Activity\Plot\Series
 */
class Thb extends ActivitySeries
{
    public const string COLOR = '#ffa500';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context, int $sensorIndex = 0)
    {
        $this->initOptions();
        if ($sensorIndex === 1) {
            $trackdataKey = Trackdata::THB_1;
            $this->Label = _('THb') . ' (2)';
        } else {
            $trackdataKey = Trackdata::THB_0;
            $this->Label = _('THb');
        }

        $this->initData($context->trackdata(), $trackdataKey);
        $this->manipulateData();
    }

    protected function manipulateData()
    {
        $this->Data = array_map($this->correctUnit(...), $this->Data);
        $this->Data = array_filter($this->Data);
    }

    /**
     * Change value by internal factor
     */
    protected function correctUnit(int $value): float
    {
        return 0.01 * $value;
    }

    protected function initOptions()
    {
        $this->Color = self::COLOR;

        $this->UnitString = 'g/dL';
        $this->UnitDecimals = 2;

        $this->TickSize = 1;
        $this->TickDecimals = 2;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }
}
