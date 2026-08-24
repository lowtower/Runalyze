<?php

declare(strict_types=1);

/**
 * This file contains class::GroundContactBalance
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Ground contact balance
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class GroundContactBalance extends ActivityPlot
{
    protected function setKey()
    {
        $this->key = 'groundcontact_balance';
    }

    protected function initData(Activity\Context $context)
    {
        $this->addSeries(
            new Series\GroundContactBalance($context)
        );
    }
}
