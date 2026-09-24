<?php

/**
 * This file contains class::Calculator
 * @package Runalyze\Calculation\Elevation
 */

namespace Runalyze\Calculation\Elevation;

use Runalyze\Calculation\Elevation\Strategy\AbstractStrategy;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\ElevationMethod;

/**
 * Elevation calculator
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Elevation
 */
class Calculator
{
    /**
     * Elevation method
     */
    protected ElevationMethod $Method;

    /**
     * Threshold
     */
    protected int $Threshold;

    /**
     * Smoothing strategy
     */
    protected ?AbstractStrategy $Strategy = null;

    /**
     * Up/down points
     */
    protected array $UpDownPoints = [];

    /**
     * Elevation up
     */
    protected int $Up = 0;

    /**
     * Elevation down
     */
    protected int $Down = 0;

    /**
     * If no options are set, the current configuration settings are used.
     */
    public function __construct(
        protected array $ElevationPoints,
        ?ElevationMethod $Method = null,
        ?int $Threshold = null
    ) {
        $this->Method = $Method instanceof ElevationMethod ? $Method : Configuration::ActivityView()->elevationMethod();
        $this->Threshold = $Threshold ?? Configuration::ActivityView()->elevationMinDiff();
    }

    public function setThreshold(int $threshold): void
    {
        $this->Threshold = $threshold;
    }

    public function setMethod(ElevationMethod $Method): void
    {
        $this->Method = $Method;
    }

    public function totalElevation(): int
    {
        return max($this->Up, $this->Down);
    }

    public function elevationUp(): int
    {
        return $this->Up;
    }

    public function elevationDown(): int
    {
        return $this->Down;
    }

    public function calculate(): void
    {
        $this->chooseStrategy();

        if ($this->ElevationPoints !== []) {
            $this->runStrategy();

            $this->Up = array_sum(array_filter($this->UpDownPoints, fn ($value): bool => $value > 0));
            $this->Down = -1 * array_sum(array_filter($this->UpDownPoints, fn ($value): bool => $value < 0));
        }
    }

    protected function chooseStrategy()
    {
        if ($this->Method->usesThreshold()) {
            $this->Strategy = new Strategy\Threshold($this->ElevationPoints, $this->Threshold);
        } elseif ($this->Method->usesDouglasPeucker()) {
            $this->Strategy = new Strategy\DouglasPeucker($this->ElevationPoints, $this->Threshold);
        } elseif ($this->Method->usesReumannWitkam()) {
            $this->Strategy = new Strategy\ReumannWitkam($this->ElevationPoints);
        } else {
            $this->Strategy = new Strategy\NoSmoothing($this->ElevationPoints);
        }
    }

    protected function runStrategy()
    {
        if ($this->Strategy instanceof Strategy\AbstractStrategy) {
            $this->Strategy->runSmoothing();
            $smoothedData = $this->Strategy->smoothedData();
        } else {
            $smoothedData = $this->ElevationPoints;
        }

        $num = count($smoothedData);
        $this->UpDownPoints = [];

        for ($i = 1; $i < $num; ++$i) {
            $this->UpDownPoints[$i - 1] = $smoothedData[$i] - $smoothedData[$i - 1];
        }
    }

    /**
     * Smoothing strategy
     */
    public function strategy(): AbstractStrategy
    {
        return $this->Strategy;
    }
}
