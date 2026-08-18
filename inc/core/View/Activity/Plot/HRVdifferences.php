<?php

declare(strict_types=1);

/**
 * This file contains class::HRVdifferences
 * @package Runalyze\View\Activity\Plot
 */
namespace Runalyze\View\Activity\Plot;

use Runalyze\View\Activity;

/**
 * Plot for hrv data: successive differences
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class HRVdifferences extends HRV
{
	/**
	 * Set key
	 */
	protected function setKey()
    {
		$this->key = 'hrv-sd';
	}

    /**
     * Init data
     */
    protected function initData(Activity\Context $context)
    {
		$this->addSeries(
		    new Series\HRVdifferencesWithoutAnomalies($context)
		);

		$this->Plot->PlotOptions['allowSelection'] = false;
	}
}
