<?php

/**
 * This file contains class::VerticalOscillation
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;
use \Plot;

/**
 * Plot for: Vertical ratio
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\View\Activity\Plot\Series
 */
class VerticalRatio extends ActivityPointSeries
{
	/**
	 * @var string
	 */
	const COLOR = 'rgb(200,100,0)';

	/**
	 * Create series
	 * @var \Runalyze\View\Activity\Context
	 */
	public function __construct(Activity\Context $context) {
		$this->initOptions();
		$this->initData($context->trackdata(), Trackdata::VERTICAL_RATIO);
		$this->manipulateData();
		$this->setManualAverage($context->activity()->verticalRatio() * 0.1);
	}

	protected function initOptions() {
		$this->Label = __('Vertical ratio');
		$this->Color = self::COLOR;

		$this->UnitString = '%';
		$this->UnitDecimals = 1;

		$this->TickSize = 1;
		$this->TickDecimals = 1;

		$this->ShowAverage = true;
		$this->ShowMaximum = false;
		$this->ShowMinimum = false;
	}

	protected function manipulateData() {
		$this->Data = array_map([$this, 'correctUnit'], $this->Data);
	}

    /**
     * Change value by internal factor
     * @param int $value
     */
    protected function correctUnit($value): float {
		return 0.1 * $value;
	}

    /**
     * Add to plot
     * @param int $yAxis
     * @param bool $addAnnotations [optional]
     */
    public function addTo(Plot $Plot, $yAxis, $addAnnotations = true) {
		parent::addTo($Plot, $yAxis, $addAnnotations);

		$this->setColorThresholdsBelow($Plot, 6.1, 7.4, 8.6, 10.1);
	}
}
