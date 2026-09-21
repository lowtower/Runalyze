<?php

/**
 * This file contains class::Swolf
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Model\Swimdata\Entity as Swimdata;
use Runalyze\View\Activity;

/**
 * Plot for: Swolf
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class Swolf extends ActivityPointSeries
{
    /**
     * @var string
     */
    public const COLOR = 'rgb(41,128,185)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initDataWithSwimdata($context);
        $this->setManualAverage($context->activity()->swolf());
    }

    /**
     * Init data
     */
    protected function initDataWithSwimdata(Activity\Context $context)
    {
        if (! $context->hasSwimdata()) {
            $this->Data = [];
            return;
        }

        $key = Swimdata::SWOLF;

        if (! $context->swimdata()->has($key)) {
            $this->Data = [];
            return;
        }

        $Collector = new DataCollectorWithSwimdata($context->trackdata(), $key, $context->swimdata());

        $this->Data = $Collector->data();
        $this->XAxis = $Collector->xAxis();
    }

    protected function initOptions()
    {
        $this->Label = __('Swolf');
        $this->Color = self::COLOR;

        $this->UnitString = '';
        $this->UnitDecimals = 0;

        $this->TickSize = 10;
        $this->TickDecimals = 0;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
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
    }
}
