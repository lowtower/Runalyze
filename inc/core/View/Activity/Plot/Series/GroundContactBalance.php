<?php

declare(strict_types=1);

/**
 * This file contains class::GroundContactBalance
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: ground contact balance
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
class GroundContactBalance extends ActivityPointSeries
{
    public const string COLOR = 'rgb(200,100,0)';

    /**
     * Create series
     */
    public function __construct(Activity\Context $context)
    {
        $this->initOptions();
        $this->initData($context->trackdata(), Trackdata::GROUNDCONTACT_BALANCE);
        $this->setManualAverage($context->activity()->groundContactBalance() / 100);
        $this->manipulateData();
    }

    protected function manipulateData()
    {
        $this->Data = array_map($this->correctUnit(...), $this->Data);
        $this->Data = array_filter($this->Data);
    }

    /**
     * Change value by internal factor
     */
    protected function correctUnit(int $value): float
    {
        return 0.01 * $value;
    }

    protected function initOptions()
    {
        $this->Label = __('Ground contact balance');
        $this->Color = self::COLOR;

        $this->UnitString = '%';
        $this->UnitDecimals = 1;

        $this->TickSize = 0.2;
        $this->TickDecimals = 1;

        $this->ShowAverage = true;
        $this->ShowMaximum = false;
        $this->ShowMinimum = false;
    }

    /**
     * Add to plot
     */
    public function addTo(Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        $Plot->Options['hooks']['draw'] = ['RunalyzePlot.flotHookColorPoints('
            . '[52.2, 50.7, 49.2, 47.7], '
            . '["' . self::COLOR_BAD . '", "' . self::COLOR_OKAY . '", "' . self::COLOR_GOOD . '", "' . self::COLOR_OKAY . '"], '
            . '"' . self::COLOR_BAD . '")',
        ];
    }
}
