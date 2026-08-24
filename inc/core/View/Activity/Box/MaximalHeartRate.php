<?php

/**
 * This file contains class::MaximalHeartRate
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Activity\HeartRate;

/**
 * Boxed value for maximal heart rate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
abstract class MaximalHeartRate extends AbstractBox
{
    public function __construct(HeartRate $heartRate)
	{
		parent::__construct(
		    $this->value($heartRate),
		    $this->unit(),
		    $this->label()
		);
	}

    /**
     * @return string
     */
    abstract protected function value(HeartRate $heartRate);

    /**
     * @return string
     */
    abstract protected function unit();

	/**
	 * @return string
	 */
	protected function label()
	{
		return __('max.') . ' ' . __('Heart rate');
	}
}
