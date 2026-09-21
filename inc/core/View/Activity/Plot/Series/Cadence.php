<?php

/**
 * This file contains class::Cadence
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;
use Runalyze\Configuration;
use Runalyze\Data;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: Cadence
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class Cadence extends ActivityPointSeries
{
    /**
     * @var string
     */
    public const COLOR = 'rgb(41,128,185)';

    protected bool $isRunning;

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->isRunning = ($context->activity()->sportid() == Configuration::General()->runningSport());
        $cadence = $this->isRunning ? new Data\Cadence\Running() : new Data\Cadence\General(0);

        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::CADENCE);
        $this->initStrings($cadence);
        $this->manipulateData($cadence);
        $this->setManualAverage($cadence->useFactor($context->activity()->cadence()));
    }

    protected function initOptions()
    {
        $this->Color = self::COLOR;

        $this->UnitDecimals = 0;

        $this->TickSize = 10;
        $this->TickDecimals = 0;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }

    protected function initStrings(Data\Cadence\AbstractCadence $cadence)
    {
        $this->Label = $cadence->label();
        $this->UnitString = $cadence->unitAsString();
    }

    protected function manipulateData(Data\Cadence\AbstractCadence $cadence)
    {
        $cadence->manipulateArray($this->Data);
    }

    /**
     * Add to plot
     */
    public function addTo(Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        if ($this->isRunning) {
            $this->setColorThresholdsAbove($Plot, 185, 173, 162, 151);
        }
    }
}
