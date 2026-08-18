<?php

declare(strict_types=1);

/**
 * This file contains class::MaximalHeartRateInPercent
 * @package Runalyze\View\Activity\Box
 */
namespace Runalyze\View\Activity\Box;

use Runalyze\Activity\HeartRate;
use Runalyze\View\Icon;

/**
 * Boxed value for maximal heart rate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class MaximalHeartRateInPercent extends MaximalHeartRate
{
    /**
     * MaximalHeartRateInPercent constructor.
     * @param bool $addWarningIfMaximalHeartRateIsExceeded
     */
    public function __construct(HeartRate $heartRate, $addWarningIfMaximalHeartRateIsExceeded = false)
    {
        parent::__construct($heartRate);

        if ($addWarningIfMaximalHeartRateIsExceeded) {
            $this->addWarningIfMaximalHeartRateIsExceeded($heartRate);
        }
    }

    /**
     * @return string
     */
    protected function value(HeartRate $heartRate)
	{
        if ($heartRate->inBPM() > 0 && $heartRate->canShowInHRmax()) {
            return $heartRate->inPercent();
        }

        return '-';
	}

    protected function unit(): string
	{
        return '&#37;';
	}

    public function addWarningIfMaximalHeartRateIsExceeded(HeartRate $heartRate)
    {
        if ($heartRate->canShowInHRmax() && $heartRate->inPercent() > 100) {
            // TODO: Using 'minus' is currently a dirty hack to color this icon
            $Icon = new Icon('fa-exclamation-circle minus');
            $Icon->setTooltip(
                __('This value exceeds your entered maximal heart rate.').'<br>'.
                __('Please adjust your maximal heart rate.')
            );

            $this->setIcon($Icon->code());
        }
    }
}
