<?php

/**
 * This file contains class::Pace
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\SecondsPer100m;
use Runalyze\Metrics\Velocity\Unit\SecondsPer100y;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500m;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500y;
use Runalyze\Metrics\Velocity\Unit\SecondsPerMile;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Parameter\Application\PaceAxisType;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\View\Activity;

/**
 * Plot for: Pace
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class Pace extends ActivitySeries
{
    /**
     * How many outliers should be cutted away?
     */
    private static int $CUT_OUTLIER_PERCENTAGE = 10;

    public const string COLOR = 'rgb(0,0,136)';

    protected bool $isRunning;

    protected AbstractPaceUnit $paceUnit;

    protected bool $adjustAxis = true;

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->paceUnit = $context->sport()->getSpeedUnit();

        $this->isRunning = ($context->sport()->getInternalProfileEnum() === SportProfile::RUNNING);

        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::PACE);
        $this->manipulateData();
    }

    protected function initOptions()
    {
        $this->Label = __('Pace');
        $this->Color = self::COLOR;

        $this->UnitString = $this->paceUnit instanceof AbstractPaceInTimeFormatUnit ? '' : $this->paceUnit->getAppendix();
        $this->UnitDecimals = 1;

        $this->TickSize = null;

        $this->ShowAverage = false;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }

    protected function manipulateData()
    {
        if ($this->paceUnit instanceof AbstractPaceInTimeFormatUnit) {
            $this->Data = array_map(fn ($v): float|int => $v == 0 ? 3600 * 1000 : 1000 * round($this->paceUnit->fromBaseUnit($v)), $this->Data);
        } else {
            $this->Data = array_map(fn ($v): float|int => $v == 0 ? 0 : round($this->paceUnit->fromBaseUnit($v), 1), $this->Data);
        }
    }

    /**
     * Add to plot
     */
    public function addTo(\Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        if ($this->Data === []) {
            return;
        }

        parent::addTo($Plot, $yAxis, $addAnnotations);

        if ($this->paceUnit instanceof AbstractPaceInTimeFormatUnit && $this->adjustAxis) {
            $this->adjustAxis($Plot, $yAxis);
        }
    }

    protected function adjustAxis(\Plot $Plot, int $yAxis)
    {
        $Plot->setYAxisTimeFormat('%M:%S', $yAxis);

        $min = min($this->Data);
        $max = max($this->Data);

        $setLimits = false;
        $autoscale = true;

        if (Configuration::ActivityView()->ignorePaceOutliers() && ($max - $min) > 2 * 60 * 1000) {
            $setLimits = true;
            $autoscale = false;
            $num = count($this->Data);
            $sorted = $this->Data;
            sort($sorted);
            $min = 10 * 1000 * floor($sorted[(int) round((self::$CUT_OUTLIER_PERCENTAGE / 2 / 100) * $num)] / 10 / 1000);
            $max = 10 * 1000 * ceil($sorted[(int) round((1 - self::$CUT_OUTLIER_PERCENTAGE / 2 / 100) * $num) - 1] / 10 / 1000);
        }

        if ($max > 50 * 60 * 1000) {
            $setLimits = true;
            $max = 50 * 60 * 1000;
        }

        $LimitMin = Configuration::ActivityView()->paceYaxisMinimum();
        $LimitMax = Configuration::ActivityView()->paceYaxisMaximum();

        if (Configuration::ActivityView()->paceAxisType()->valueAsString() === PaceAxisType::AS_SPEED) {
            $min = $LimitMin->automatic() ? $min : $LimitMin->value() * 1000;
            $max = $LimitMax->automatic() ? $max : $LimitMax->value() * 1000;

            $this->setYAxisForReversePace($Plot, $yAxis, $min, $max, ! $LimitMin->automatic(), ! $LimitMax->automatic());
        } else {
            if (! $LimitMin->automatic() || ! $LimitMax->automatic()) {
                $setLimits = true;
                $autoscale = false;

                if (! $LimitMin->automatic() && $min < 1000 * $LimitMin->value()) {
                    $min = 1000 * $LimitMin->value();
                } else {
                    $min = 60 * 1000 * floor($min / 60 / 1000);
                }

                if (! $LimitMax->automatic() && $max > 1000 * $LimitMax->value()) {
                    $max = 1000 * $LimitMax->value();
                } else {
                    $max = 60 * 1000 * floor($max / 60 / 1000);
                }
            }

            if ($setLimits) {
                $Plot->setYLimits($yAxis, $min, $max, $autoscale);
            }
        }

        switch (Configuration::ActivityView()->paceAxisType()->valueAsString()) {
            case PaceAxisType::AS_SPEED:
                $Plot->setYAxisPaceReverse($yAxis);
                break;
            case PaceAxisType::REVERSE:
                $Plot->setYAxisReverse($yAxis);
                break;
        }
    }

    private function setYAxisForReversePace(Plot $plot, int $yAxis, int $dataMin, int $dataMax, bool $forceMin, bool $forceMax): void
    {
        if ($this->paceUnit instanceof SecondsPerMile) {
            $ticks = [240, 300, 360, 450, 600, 900, 3600];
        } elseif ($this->paceUnit instanceof SecondsPer100m || $this->paceUnit instanceof SecondsPer100y) {
            $ticks = [10, 60, 120, 180, 240, 720];
        } elseif ($this->paceUnit instanceof SecondsPer500m || $this->paceUnit instanceof SecondsPer500y) {
            $ticks = [60, 120, 180, 240, 720];
        } else { // defaults to min/km;
            $ticks = [120, 180, 240, 300, 360, 480, 600, 3600];
        }

        $ticks = array_map(fn (int $v): int => $v * 1000, $ticks);

        $firstIndex = 0;
        $lastIndex = count($ticks) - 1;

        foreach ($ticks as $i => $tick) {
            if ($tick <= $dataMin) {
                $firstIndex = $i;
            }

            if ($tick < $dataMax) {
                $lastIndex = $i + 1;
            }
        }

        $lastIndex = min($lastIndex, count($ticks) - 1);
        $min = $forceMin ? $dataMin : $ticks[$firstIndex];
        $max = $forceMax ? $dataMax : $ticks[$lastIndex];
        $ticks = array_slice($ticks, max(0, $firstIndex - 1), max(1, $lastIndex - $firstIndex + 1));

        $plot->setYLimits($yAxis, $min, $max, false);
        $plot->setYAxisLabels($yAxis, $ticks);
    }
}
