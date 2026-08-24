<?php

declare(strict_types=1);

/**
 * This file contains class::Stroke
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: stroke
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\View\Activity\Plot
 */
class Stroke extends ActivityPlot
{
    protected function setKey()
    {
        $this->key = 'stroke';
    }

    protected function initData(Activity\Context $context)
    {
        $this->addSeries(
            new Series\Stroke($context)
        );
    }
}
