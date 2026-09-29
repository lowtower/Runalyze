<?php

declare(strict_types=1);

/**
 * This file contains class::DataCollector
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Model\Trackdata\Loop;

/**
 * Collect data from trackdata
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class DataCollector
{
    /**
     * @var int
     */
    public const X_AXIS_INDEX = 0;

    /**
     * @var int
     */
    public const X_AXIS_TIME = 1;

    /**
     * @var int
     */
    public const X_AXIS_DISTANCE = 2;

    protected string $Key;

    protected \Runalyze\Model\Trackdata\Loop $Loop;

    /**
     * Step distance
     * @var int [m]
     */
    protected int $StepDistance;

    protected int $XAxis = 0;

    /**
     * @var float[]|int[]
     */
    protected array $Data = [];

    protected \Runalyze\Parameter\Application\ActivityPlotPrecision $Precision;

    protected bool $KnowsDistance;

    /**
     * Construct collector
     * @throws \InvalidArgumentException
     */
    public function __construct(Trackdata $trackdata, string $key)
    {
        if (! $trackdata->has($key)) {
            throw new \InvalidArgumentException('Trackdata has no data for "' . $key . '".');
        }

        $this->Key = $key;
        $this->Precision = Configuration::ActivityView()->plotPrecision();
        $this->KnowsDistance = $trackdata->has(Trackdata::DISTANCE);

        $this->init($trackdata);
        $this->collect();
    }

    public function data(): array
    {
        return $this->Data;
    }

    /**
     * Type of x-axis
     */
    public function xAxis(): int
    {
        return $this->XAxis;
    }

    /**
     * Init loop
     */
    protected function init(Trackdata $trackdata)
    {
        $this->Loop = new Loop($trackdata);

        $this->defineStepSize($trackdata);
        $this->defineXAxis($trackdata);
    }

    /**
     * Collect data
     */
    protected function collect()
    {
        $useCurrentValue = ($this->Key === Trackdata::DISTANCE || $this->Key === Trackdata::TIME);

        do {
            $this->move();

            $value = $useCurrentValue ? $this->Loop->current($this->Key) : $this->Loop->average($this->Key);

            if ($this->XAxis === self::X_AXIS_DISTANCE) {
                $this->Data[(string) $this->Loop->current(Trackdata::DISTANCE)] = $value;
            } elseif ($this->XAxis === self::X_AXIS_TIME) {
                $this->Data[$this->Loop->current(Trackdata::TIME) . '000'] = $value;
            } else {
                $this->Data[] = $value;
            }
        } while (! $this->Loop->isAtEnd());
    }

    /**
     * Get next step for plot data
     */
    protected function move()
    {
        if ($this->KnowsDistance && $this->Precision->byDistance()) {
            $this->Loop->moveDistance($this->StepDistance);
        } else {
            $this->Loop->nextStep();
        }
    }

    /**
     * Set step size
     */
    protected function defineStepSize(Trackdata $trackdata)
    {
        if ($this->Precision->byPoints() && $trackdata->num() > $this->Precision->numberOfPoints()) {
            $this->Loop->setStepSize((int) round($trackdata->num() / $this->Precision->numberOfPoints()));
        } elseif ($this->Precision->byDistance()) {
            $this->StepDistance = $this->Precision->distanceStep() / 1000;
        }
    }

    /**
     * Define x-axis
     */
    protected function defineXAxis(Trackdata $trackdata)
    {
        if (Configuration::ActivityView()->usesTimeAsXAxis() && $trackdata->has(Trackdata::TIME) && $trackdata->totalTime() > 0) {
            $this->XAxis = self::X_AXIS_TIME;
        } elseif ($trackdata->has(Trackdata::DISTANCE) && $trackdata->totalDistance() > 0) {
            $this->XAxis = self::X_AXIS_DISTANCE;
        } elseif ($trackdata->has(Trackdata::TIME) && $trackdata->totalTime() > 0) {
            $this->XAxis = self::X_AXIS_TIME;
        } else {
            $this->XAxis = self::X_AXIS_INDEX;
        }
    }
}
