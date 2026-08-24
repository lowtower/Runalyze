<?php

/**
 * This file contains class::Heartrate
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: Heartrate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class Heartrate extends ActivitySeries
{
    /**
     * @var string
     */
    public const COLOR = 'rgb(136,0,0)';

    /**
     * @var mixed
     */
    protected $Factor;

    /**
     * @var int
     */
    protected $HRmax;

    /**
     * @var int
     */
    protected $HRrest;

    /**
     * Create series
     * @var \Runalyze\View\Activity\Context
     */
    public function __construct(Activity\Context $context)
    {
        $this->setManipulationFactor();
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::HEARTRATE, true);
        $this->manipulateData();
        $this->setManualAverage(round($this->correctUnit($context->activity()->hrAvg())));
    }

    protected function initOptions()
    {
        $this->Label = __('Heartrate');
        $this->Color = self::COLOR;

        $this->UnitString = $this->unitAsString();
        $this->UnitDecimals = 0;

        $this->TickSize = 5;
        $this->TickDecimals = 0;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }

    /**
     * Set manipulation facotr
     */
    protected function setManipulationFactor()
    {
        $this->Factor = 1;
        $this->HRmax = Configuration::Data()->HRmax();
        $this->HRrest = Configuration::Data()->HRrest();

        if (\Runalyze\Context::Athlete()->knowsMaximalHeartRate() && ! Configuration::General()->heartRateUnit()->isBPM()) {
            if (\Runalyze\Context::Athlete()->knowsRestingHeartRate() && Configuration::General()->heartRateUnit()->isHRreserve()) {
                $this->Factor = false;
            } else {
                $this->Factor = 100 / $this->HRmax;
            }
        }
    }

    protected function manipulateData()
    {
        $this->Data = array_map([$this, 'correctUnit'], $this->Data);
    }

    /**
     * Change value by internal factor
     * @param int $value
     * @return float
     */
    protected function correctUnit($value)
    {
        if ($this->Factor === false) {
            return 100 * ($value - $this->HRrest) / ($this->HRmax - $this->HRrest);
        }

        return $this->Factor * $value;
    }

    /**
     * Add to plot
     * @param int $yAxis
     * @param bool $addAnnotations [optional]
     */
    public function addTo(\Plot $Plot, $yAxis, $addAnnotations = true)
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        $max = $this->showsAsPercentage() ? 100 : $this->HRmax;

        if (! empty($this->Data)) {
            $Plot->setYLimits($yAxis, 10 * floor(min($this->Data) / 10), $max);

            $Plot->addMarkingArea('y' . $yAxis, $max * 1, $max * 0.9, 'rgba(255,100,100,0.3)');
            $Plot->addMarkingArea('y' . $yAxis, $max * 0.9, $max * 0.8, 'rgba(255,100,100,0.2)');
            $Plot->addMarkingArea('y' . $yAxis, $max * 0.8, $max * 0.7, 'rgba(255,100,100,0.1)');
            $Plot->addMarkingArea('y' . $yAxis, $max * 0.7, $max * 0.6, 'rgba(255,100,100,0.05)');
        }
    }

    /**
     * Get unit for current pulse mode
     */
    protected function unitAsString(): string
    {
        if ($this->showsAsPercentage()) {
            return '%';
        }

        return 'bpm';
    }

    protected function showsAsPercentage(): bool
    {
        return $this->Factor != 1;
    }
}
