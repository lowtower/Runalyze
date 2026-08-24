<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

class BrakingGs extends ActivityPlot
{
    protected function setKey()
    {
        $this->key = 'braking_gs';
    }

    protected function initData(Activity\Context $context)
    {
        $this->addSeries(new Series\BrakingGs($context, true), 1);
        $this->addSeries(new Series\BrakingGs($context, false), 1);
    }
}
