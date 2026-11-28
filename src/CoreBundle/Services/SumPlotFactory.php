<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use PlotMonthSumData;
use PlotSumData;
use PlotWeekSumData;
use Symfony\Component\HttpFoundation\InputBag;

final class SumPlotFactory
{
    public function create(InputBag $query, string $navigationUrl): PlotSumData
    {
        if ($query->getString('type') === 'week') {
            return new PlotWeekSumData($query, $navigationUrl, 'week');
        }

        return new PlotMonthSumData($query, $navigationUrl, 'month');
    }
}
