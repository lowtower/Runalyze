<?php

declare(strict_types=1);

/**
 * This file contains class::ActivityPlot
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Plot;
use Runalyze\View\Activity;

/**
 * Plot for activity data
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
abstract class ActivityPlot
{
    protected int $WIDTH = 600;

    protected int $HEIGHT = 190;

    /**
     * Plot
     */
    protected \Plot $Plot;

    /**
     * Use standard x-axis?
     */
    protected bool $useStandardXaxis = true;

    /**
     * Key
     */
    protected string $key = '';

    /**
     * Data
     */
    protected array $Data = [];

    /**
     * CSS id
     */
    protected string $id;

    abstract protected function setKey();

    abstract protected function initData(Activity\Context $context);

    /**
     * Construct
     */
    public function __construct(Activity\Context $context)
    {
        $this->setKey();
        $this->setID($context->activity()->id());
        $this->initPlot();
        $this->initData($context);

        if (! $this->dataIsAvailable()) {
            $this->Plot->raiseError(__('No plot available.'));
        }
    }

    final protected function addSeries(\Runalyze\View\Plot\Series $series, int $yAxis = 1, bool $addAnnotations = true)
    {
        $series->addTo($this->Plot, $yAxis, $addAnnotations);
    }

    /**
     * @param \Runalyze\View\Plot\Series[] $series
     */
    protected function addMultipleSeries(array $series)
    {
        $yaxis = 1;

        foreach ($series as $object) {
            if ($object instanceof \Runalyze\View\Plot\Series && ! $object->isEmpty()) {
                $this->addSeries($object, $yaxis, false);
                $yaxis++;
            }
        }
    }

    /**
     * @param int $index natural index starting by 1 (for y-axis) and using the 0-indexed version for series
     */
    public function hideYAxisAndSeries(int $index)
    {
        $this->hideYAxis($index);
        $this->hideSeries($index - 1);
    }

    protected function hideYAxis(int $yAxis)
    {
        $this->Plot->hideYAxis($yAxis);
    }

    protected function hideSeries(int $series)
    {
        $this->Plot->setLineWidth($series, 0);
        $this->Plot->setShadowSize($series, 0);
    }

    private function dataIsAvailable(): bool
    {
        foreach ($this->Plot->Data as $key => $Data) {
            if (! isset($Data['data']) || empty($Data['data'])) {
                unset($this->Plot->Data[$key]);
            }
        }

        return count($this->Data) >= 2 || $this->Plot->Data !== [];
    }

    private function initPlot(): void
    {
        $this->Plot = new Plot($this->getCSSid(), $this->WIDTH, $this->HEIGHT);
    }

    private function setID(?int $id)
    {
        $this->id = $this->key . '_' . $id;
    }

    final public function getKey(): string
    {
        return $this->key;
    }

    final protected function getCSSid(): string
    {
        return $this->id;
    }

    /**
     * Output JS
     */
    final public function display(): void
    {
        echo Plot::getInnerDivFor($this->getCSSid(), $this->WIDTH, $this->HEIGHT);
        $this->Plot->outputJavaScript();
    }
}
