<?php

declare(strict_types=1);

/**
 * This file contains class::VerticalOscillation
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: Vertical ratio
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\View\Activity\Plot\Series
 */
class VerticalRatio extends ActivityPointSeries
{
    public const string COLOR = 'rgb(200,100,0)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::VERTICAL_RATIO);
        $this->manipulateData();

        $activityVerticalRatio = $context->activity()->verticalRatio();

        if ($activityVerticalRatio !== null) {
            $this->setManualAverage($activityVerticalRatio * 0.1);
        }
    }

    protected function initOptions()
    {
        $this->Label = __('Vertical ratio');
        $this->Color = self::COLOR;

        $this->UnitString = '%';
        $this->UnitDecimals = 1;

        $this->TickSize = 1;
        $this->TickDecimals = 1;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }

    protected function manipulateData()
    {
        $this->Data = array_map($this->correctUnit(...), $this->Data);
        $this->Data = array_filter($this->Data);
    }

    /**
     * Change value by internal factor, gaps (null values) are filtered out afterwards
     */
    protected function correctUnit(int|float|null $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return 0.1 * $value;
    }

    /**
     * Add to plot
     */
    public function addTo(Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        $this->setColorThresholdsBelow($Plot, 6.1, 7.4, 8.6, 10.1);
    }
}
