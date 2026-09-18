<?php

/**
 * This file contains class::Series
 * @package Runalyze\View\Plot
 */

namespace Runalyze\View\Plot;

use Plot;
use Runalyze\View\Activity\Plot\ActivityPlot;

/**
 * Plot series
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Plot
 */
class Series
{
    protected string $Label;

    protected string $Color;

    protected array $Data;

    protected string $UnitString = '';

    protected int $UnitDecimals = 0;

    protected float $UnitFactor = 1.0;

    protected ?float $TickSize = null;

    protected int $TickDecimals = 0;

    protected bool $ShowAverage = false;

    protected bool $ShowMaximum = false;

    protected bool $ShowMinimum = false;

    /**
     * Manual average
     * A manual average can be used instead of calculating the average of the series
     * @var mixed
     */
    protected $ManualAverage = false;

    protected ?int $YAxis = null;

    public function setLabel(string $label): void
    {
        $this->Label = $label;
    }

    public function label(): string
    {
        return $this->Label;
    }

    public function setColor(string $color): void
    {
        $this->Color = $color;
    }

    public function setData(array $data): void
    {
        $this->Data = $data;
    }

    public function isEmpty(): bool
    {
        return $this->Data === [];
    }

    /**
     * Set properties
     * @param int $yAxis
     * @param bool $addAnnotations [optional]
     */
    public function addTo(Plot $Plot, $yAxis, bool $addAnnotations = true): void
    {
        if ($this->Data === []) {
            return;
        }

        $this->YAxis = $yAxis;

        if ($this->UnitString === 'time') {
            foreach ($this->Data as $k => $v) {
                $this->Data[$k] = $v * '1000';
            }
        }

        $Plot->Data[] = [
            'label' => $this->Label,
            'color' => $this->Color,
            'data' => $this->Data,
            'yaxis' => $yAxis,
        ];

        if ($this->UnitString !== '') {
            if ($this->UnitString === 'time') {
                $Plot->setYAxisAsTime($yAxis);
                $Plot->setYAxisTimeFormat('%H:%M:%S', $yAxis);
            } else {
                $Plot->addYUnit($yAxis, $this->UnitString, $this->UnitDecimals);
            }
        }

        if ($this->TickSize !== null) {
            $Plot->setYTicks($yAxis, (int) $this->TickSize, (bool) $this->TickDecimals);
        }

        if ($addAnnotations && $this->ShowAverage) {
            $avg = $this->avg();

            $Plot->addThreshold('y' . $yAxis, $avg, 'rgba(0,0,0,0.5)');
            $Plot->addAnnotation(0, $avg, __('avg:') . ' ' . $avg . ' ' . $this->UnitString);
        }

        if ($addAnnotations && $this->ShowMaximum) {
            $max = max($this->Data);
            $maxX = array_keys($this->Data, $max);

            $Plot->addAnnotation($maxX[0], round($max), round($max) . $this->UnitString);
        }

        if ($addAnnotations && $this->ShowMinimum) {
            $min = min($this->Data);
            $minX = array_keys($this->Data, $min);

            $Plot->addAnnotation($minX[0], round($min), round($min) . $this->UnitString);
        }
    }

    public function hideIn(ActivityPlot $plot): void
    {
        if ($this->YAxis !== null) {
            $plot->hideYAxisAndSeries($this->YAxis);
        }
    }

    /**
     * Average
     * @param int $decimals [optional]
     * @return int|float
     */
    protected function avg(int $decimals = 0)
    {
        if ($this->ManualAverage !== false) {
            return $this->ManualAverage;
        }

        return round(array_sum($this->Data) / count($this->Data), $decimals);
    }

    /**
     * @param int|float $value
     */
    protected function setManualAverage($value)
    {
        $this->ManualAverage = $value;
    }
}
