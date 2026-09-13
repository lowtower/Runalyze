<?php

/**
 * This file contains class::ActivitySeries
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;
use Runalyze\Configuration;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;

/**
 * Activity series
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
abstract class ActivitySeries extends \Runalyze\View\Plot\Series
{
    /**
     * @var int enum
     */
    protected $XAxis = 0;

    abstract public function __construct(Context $context);

    /**
     * @param string $key
     * @param bool $fillGaps try to fill gaps (zero values)
     */
    protected function initData(Trackdata $trackdata, $key, $fillGaps = false)
    {
        if (! $trackdata->has($key)) {
            $this->Data = [];
            return;
        }

        if ($fillGaps) {
            $this->fillGaps($trackdata, $key);
        }

        $Collector = new DataCollector($trackdata, $key);

        $this->Data = $Collector->data();
        $this->XAxis = $Collector->xAxis();
    }

    /**
     * Init data
     * @param string $key
     */
    protected function fillGaps(Trackdata $trackdata, $key)
    {
        $data = $trackdata->get($key);
        $last = $data[0];

        foreach ($data as &$val) {
            if ($val == 0) {
                $val = $last;
            }

            $last = $val;
        }

        $trackdata->set($key, $data);
    }

    /**
     * Add to plot
     * @param int $yAxis
     * @param bool $addAnnotations [optional]
     */
    public function addTo(Plot $Plot, $yAxis, bool $addAnnotations = true)
    {
        if ($this->Data === []) {
            return;
        }

        parent::addTo($Plot, $yAxis, $addAnnotations);

        switch ($this->XAxis) {
            case DataCollector::X_AXIS_DISTANCE:
                $Plot->setXUnitFactor(
                    Configuration::General()->distanceUnitSystem()->distanceToPreferredUnitFactor(),
                    Configuration::General()->distanceUnitSystem()->distanceUnit()
                );

                $stepSize = Configuration::General()->distanceUnitSystem()->distanceToKmFactor();

                if ($stepSize != round($stepSize)) {
                    $totalDistanceInKm = array_key_last($this->Data);

                    while ($totalDistanceInKm / $stepSize > 15) {
                        $stepSize *= 2;
                    }

                    while ($totalDistanceInKm / $stepSize < 4) {
                        $stepSize /= 2;
                    }

                    $Plot->Options['xaxis']['tickSize'] = $stepSize;
                }

                break;

            case DataCollector::X_AXIS_TIME:
                $Plot->setXAxisAsTime();
                $Plot->setXAxisTimeFormat('%h:%M:%S');
                $Plot->Options['xaxis']['ticks'] = 5;
                break;

            case DataCollector::X_AXIS_INDEX:
                $Plot->hideXLabels();
        }
    }
}
