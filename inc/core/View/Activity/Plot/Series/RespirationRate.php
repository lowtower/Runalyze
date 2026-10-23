<?php

declare(strict_types=1);

/**
 * This file contains class::RespirationRate
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: RespirationRate
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class RespirationRate extends ActivitySeries
{
    public const string COLOR = 'rgb(100,0,200)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::RESPIRATION_RATE);
    }

    protected function initOptions()
    {
        $this->Label = __('Atemfrequenz');
        $this->Color = self::COLOR;

        $this->UnitString = 'brpm';
        $this->UnitDecimals = 1;

        $this->TickSize = 10;
        $this->TickDecimals = 0;

        $this->ShowAverage = true;
        $this->ShowMaximum = true;
        $this->ShowMinimum = true;
    }
}
