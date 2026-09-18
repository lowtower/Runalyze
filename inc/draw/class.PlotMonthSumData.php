<?php

/**
 * This file contains class::PlotMonthSumData
 * @package Runalyze\Plot
 */

use Runalyze\Util\LocalTime;
use Runalyze\Util\Time;

/**
 * Plot month data
 * @package Runalyze\Plot
 */
class PlotMonthSumData extends PlotSumData
{
    public function __construct()
    {
        $this->timerStart = Request::param('y') == parent::LAST_6_MONTHS ? 7 : 1;
        $this->timerEnd = 12;

        parent::__construct();
    }

    protected function getCSSid(): string
    {
        return 'monthKM' . $this->Year . '_' . $this->Sport->id();
    }

    protected function getTitle(): string
    {
        return __('Monthly chart:');
    }

    protected function getXLabels(): array
    {
        $months = [];
        $add = ($this->Year === parent::LAST_6_MONTHS || $this->Year === parent::LAST_12_MONTHS) ? date('m') : 0;
        $i = 0;

        for ($m = $this->timerStart; $m <= $this->timerEnd; $m++) {
            $months[] = [$i, Time::month((string) ($m + $add), true)];
            $i++;
        }

        return $months;
    }

    /**
     * Timer table for query
     */
    protected function timer(): string
    {
        if ($this->Year === parent::LAST_6_MONTHS) {
            return '((MONTH(FROM_UNIXTIME(`time`)) + 12 - ' . date('m') . ' - 1)%12 + 1)';
        }

        if ($this->Year === parent::LAST_12_MONTHS) {
            return '((MONTH(FROM_UNIXTIME(`time`)) + 12 - ' . date('m') . ' - 1)%12 + 1)';
        }

        return 'MONTH(FROM_UNIXTIME(`time`))';
    }

    protected function beginningOfLast6Months(): int
    {
        return LocalTime::fromString('first day of -5 months 00:00')->getTimestamp();
    }

    protected function beginningOfLast12Months(): int
    {
        return LocalTime::fromString('first day of -11 months 00:00')->getTimestamp();
    }

    protected function factorForWeekKm(): float
    {
        return 365 / 12 / 7;
    }
}
