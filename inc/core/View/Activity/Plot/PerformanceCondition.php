<?php

declare(strict_types=1);

/**
 * This file contains class::PerformanceCondition
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: PerformanceCondition
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class PerformanceCondition extends ActivityPlot
{
	protected function setKey() {
		$this->key = 'performancecondition';
	}

    protected function initData(Activity\Context $context) {
		$this->addSeries(
		    new Series\PerformanceCondition($context)
		);
	}
}
