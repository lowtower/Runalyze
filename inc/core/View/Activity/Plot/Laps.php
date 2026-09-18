<?php

/**
 * This file contains class::Laps
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Helper;
use Runalyze\Activity\Pace as PaceObject;
use Runalyze\Configuration;
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

    /**
     * Unit
     */
    protected \Runalyze\Activity\PaceUnit\AbstractUnit $PaceUnit;

    abstract protected function loadData(Activity\Context $context);

    protected function initData(Activity\Context $context)
    {
        $this->PaceUnit = $context->sport()
            ->legacyPaceUnit();

        $this->SplitsAreNotComplete = $this->splitsAreNotComplete($context);
        $this->loadData($context);

        if ($this->Data !== [] && $this->PaceUnit->isTimeFormat()) {
            $min = Helper::floorFor(min($this->Data), 30000);
            $max = Helper::ceilFor(max($this->Data), 30000);

            $this->Plot->setYAxisTimeFormat('%M:%S');
        } elseif ($this->Data !== []) {
            $min = floor(min($this->Data));
            $max = ceil(max($this->Data));

            $Pace = new PaceObject(0, 1);
            $Pace->setUnit($this->PaceUnit);
            $this->Plot->addYUnit(1, str_replace('&nbsp;', '', $Pace->appendix()), 1);
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
            $demandedPace = (float) $this->PaceUnit->rawValue($this->demandedPace);

            if ($this->PaceUnit->isTimeFormat()) {
                $demandedPace *= 1000;
            }

            $this->Plot->addThreshold('y', round($demandedPace), 'rgb(180,0,0)');
            // $this->Plot->addAnnotation(count($Data)-1, round($demandedPace), 'Soll: '.$this->PaceUnit->format($this->demandedPace), -10, -7);
        }

        if ($this->achievedPace > 0) {
            $achievedPace = (float) $this->PaceUnit->rawValue($this->achievedPace);

            if ($this->PaceUnit->isTimeFormat()) {
                $achievedPace *= 1000;
            }

            $this->Plot->addThreshold('y', round($achievedPace), 'rgb(0,180,0)');
            $this->Plot->addAnnotation(0, round($achievedPace), __('avg:') . ' ' . $this->PaceUnit->format($this->achievedPace), -40, -7);
        }
    }

    /**
     * Splits are not complete
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
