<?php

declare(strict_types=1);

/**
 * This file contains class::Temperature
 * @package Runalyze\View\Activity\Plot
 */
namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for: Temperature
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class Temperature extends ActivityPlot
{
	protected function setKey() {
		$this->key = 'temperature';
	}

    protected function initData(Activity\Context $context) {
		$this->addSeries(
		    new Series\Temperature($context)
		);
	}
}
