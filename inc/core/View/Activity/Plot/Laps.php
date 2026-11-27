<?php

/**
 * This file contains class::Laps
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Helper;
use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\View\Activity;

/**
 * Plot for: Laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
abstract class Laps extends ActivityPlot
{
    /**
     * Demanded pace in s/km
     */
    protected float $demandedPace = 0.0;

    /**
     * Achieved pace in s/km
     */
    protected float $achievedPace = 0.0;

    protected array $Labels = [];

    protected bool $SplitsAreNotComplete;

    protected string $title;

    private AbstractPaceUnit $paceUnit;

    abstract protected function loadData(Activity\Context $context);

    protected function initData(Activity\Context $context)
    {
        $this->paceUnit = $context->sport()->getSpeedUnit();

        $this->SplitsAreNotComplete = $this->splitsAreNotComplete($context);
        $this->loadData($context);

        if ($this->Data !== [] && $this->paceUnit instanceof AbstractPaceInTimeFormatUnit) {
            $min = Helper::floorFor(min($this->Data), 30000);
            $max = Helper::ceilFor(max($this->Data), 30000);

            $this->Plot->setYAxisTimeFormat('%M:%S');
        } elseif ($this->Data !== []) {
            $min = floor(min($this->Data));
            $max = ceil(max($this->Data));

            $this->Plot->addYUnit(1, $this->paceUnit->getAppendix(), 1);
        } else {
            // #TSC if we have no distance data set nothing => will result in "no data" notice
            return;
        }

        if (Configuration::ActivityView()->startSplitsAtZero()) {
            $min = 0;
        }

        $this->Plot->setYLimits(1, $min, $max, false);
        $this->Plot->setXLabels($this->Labels);
        $this->Plot->showBars(true);

        $this->Plot->setYTicks(1, 1, false);
        $this->Plot->Options['xaxis']['show'] = true; // force to show xaxis-labels, even if no time or distance array is given

        $this->addAnnotations();
    }

    protected function addAnnotations()
    {
        if ($this->demandedPace > 0) {
            $this->Plot->addThreshold('y', $this->thresholdValue($this->demandedPace), 'rgb(180,0,0)');
        }

        if ($this->achievedPace > 0) {
            $achievedPace = $this->thresholdValue($this->achievedPace);
            $formattedPace = new PaceFormatter($this->paceUnit)->value($this->achievedPace);
            $this->Plot->addThreshold('y', $achievedPace, 'rgb(0,180,0)');
            $this->Plot->addAnnotation(0, $achievedPace, __('avg:') . ' ' . $formattedPace, -40, -7);
        }
    }

    protected function barValue(int $secondsPerKilometer): float
    {
        $value = $this->paceUnit->fromBaseUnit($secondsPerKilometer);

        return $this->paceUnit instanceof AbstractPaceInTimeFormatUnit ? 1000 * round($value) : round($value, 1);
    }

    private function thresholdValue(float $secondsPerKilometer): float
    {
        $value = $this->paceUnit->fromBaseUnit($secondsPerKilometer);

        return round($this->paceUnit instanceof AbstractPaceInTimeFormatUnit ? 1000 * $value : $value);
    }

    /**
     * "Complete" means: all laps are active and fill total distance
     */
    protected function splitsAreNotComplete(Activity\Context $context): bool
    {
        if ($context->activity()->splits()->isEmpty() || $context->activity()->splits()->totalDistance() <= 0) {
            return false;
        }

        if ($context->activity()->splits()->hasActiveAndInactiveLaps()) {
            return true;
        }

        return abs($context->activity()->splits()->totalDistance() - $context->activity()->distance()) > 0.02 * $context->activity()
            ->distance();
    }
}
