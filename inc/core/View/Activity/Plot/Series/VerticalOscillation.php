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
 * Plot for: Vertical oscillation
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class VerticalOscillation extends ActivityPointSeries
{
    /**
     * @var string
     */
    public const COLOR = 'rgb(200,100,0)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::VERTICAL_OSCILLATION);
        $this->manipulateData();
        $this->setManualAverage($context->activity()->verticalOscillation() * 0.1);
    }

    protected function initOptions()
    {
        $this->Label = __('Vertical oscillation');
        $this->Color = self::COLOR;

        $this->UnitString = 'cm';
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
    }

    /**
     * Change value by internal factor
     */
    protected function correctUnit(int $value): float
    {
        return 0.1 * $value;
    }

    /**
     * Add to plot
     */
    public function addTo(Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        $this->setColorThresholdsBelow($Plot, 6.7, 8.3, 10.0, 11.8);
    }
}
