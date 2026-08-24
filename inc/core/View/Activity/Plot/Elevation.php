<?php

/**
 * This file contains class::Elevation
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Elevation
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class Elevation extends ActivityPlot
{
    protected function setKey()
    {
        $this->key = 'elevation';
    }

    protected function initData(Activity\Context $context)
    {
        /** @var \Runalyze\View\Plot\Series[] $allSeries */
        $allSeries = [
            new Series\Elevation($context),
            new Series\Gradient($context),
        ];

        $this->addMultipleSeries($allSeries);
    }
}
