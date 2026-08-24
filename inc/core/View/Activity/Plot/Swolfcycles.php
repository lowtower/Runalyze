<?php

declare(strict_types=1);

/**
 * This file contains class::swolf
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Swolf
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\View\Activity\Plot
 */
class Swolfcycles extends ActivityPlot
{
    protected function setKey()
    {
        $this->key = 'swolfcycles';
    }

    protected function initData(Activity\Context $context)
    {
        $this->addSeries(
            new Series\Swolfcycles($context)
        );
    }
}
