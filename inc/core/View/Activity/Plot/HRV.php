<?php

/**
 * This file contains class::HRV
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for hrv data
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class HRV extends ActivityPlot {
	/**
	 * Use standard x-axis?
	 * @var bool
	 */
	protected $useStandardXaxis = false;

	/**
	 * Set key
	 */
	protected function setKey() {
		$this->key = 'hrv';
	}

    /**
     * Init data
     */
    protected function initData(Activity\Context $context) {
		$this->addSeries(
		    new Series\HRVwithoutAnomalies($context)
		);

		$this->Plot->PlotOptions['allowSelection'] = false;
	}
}
