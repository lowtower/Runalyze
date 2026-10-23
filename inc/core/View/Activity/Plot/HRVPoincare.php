<?php

declare(strict_types=1);

/**
 * This file contains class::HRVPoincare
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\Calculation\HRV\PoincareCollector;
use Runalyze\View\Activity;

/**
 * Poincare plot for hrv data
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class HRVPoincare extends ActivityPlot
{
    /**
     * Use standard x-axis?
     */
    #[\Override]
    protected bool $useStandardXaxis = false;

    protected int $PointSize = 1;

    protected function setKey()
    {
        $this->key = 'hrvpoincare';
    }

    protected function initData(Activity\Context $context)
    {
        $Collector = new PoincareCollector($context->hrv());
        $this->Data = $Collector->data();

        if ($this->Data === []) {
            return;
        }

        $this->Plot->Data[] = [
            'label' => __('R-R interval') . ' n+1',
            'color' => 'rgb(0,0,0)',
            'data' => $Collector->data(),
        ];

        // TODO: ensure symmetric x-/y-axis

        $this->Plot->addYUnit(1, 'ms');
        $this->Plot->setXUnit('ms');
        $this->Plot->setXLabel(__('R-R interval') . ' n');
        $this->Plot->smoothing(false);
        $this->Plot->showPoints();

        $this->Plot->PlotOptions['allowSelection'] = false;
    }
}
