<?php

declare(strict_types=1);

/**
 * This file contains class::ActivityPointSeries
 * @package Runalyze\View\Activity\Plot\Series
 */

namespace Runalyze\View\Activity\Plot\Series;

use Plot;

/**
 * Activity series with points
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot\Series
 */
abstract class ActivityPointSeries extends ActivitySeries
{
    public const COLOR_PERFECT = '#8E44AD';

    public const COLOR_GREAT = '#2980B9';

    public const COLOR_GOOD = '#27AE60';

    public const COLOR_OKAY = '#E67E22';

    public const COLOR_BAD = '#E74C3C';

    protected int $PointSize = 2;

    /**
     * Add to plot
     */
    public function addTo(Plot $Plot, ?int $yAxis, bool $addAnnotations = true): void
    {
        parent::addTo($Plot, $yAxis, $addAnnotations);

        $Plot->showPoints($this->PointSize);
        $Plot->smoothing(false);
    }

    /**
     * Set thresholds for point colors
     * @param float $perfect points above this limit will be colored as 'perfect'
     * @param float $great points above this limit will be colored as 'great'
     * @param float $good points above this limit will be colored as 'good'
     * @param float $okay points above this limit will be colored as 'okay'
     */
    protected function setColorThresholdsAbove(Plot $Plot, float $perfect, float $great, float $good, float $okay)
    {
        $Plot->Options['hooks']['draw'] = ['RunalyzePlot.flotHookColorPoints('
            . '[' . $perfect . ', ' . $great . ', ' . $good . ', ' . $okay . '], '
            . '["' . self::COLOR_PERFECT . '", "' . self::COLOR_GREAT . '", "' . self::COLOR_GOOD . '", "' . self::COLOR_OKAY . '"], '
            . '"' . self::COLOR_BAD . '")',
        ];
    }

    /**
     * Set thresholds for point colors
     * @param float $perfect points below this limit will be colored as 'perfect'
     * @param float $great points below this limit will be colored as 'great'
     * @param float $good points below this limit will be colored as 'good'
     * @param float $okay points below this limit will be colored as 'okay'
     */
    protected function setColorThresholdsBelow(Plot $Plot, float $perfect, float $great, float $good, float $okay)
    {
        $Plot->Options['hooks']['draw'] = ['RunalyzePlot.flotHookColorPoints('
            . '[' . $okay . ', ' . $good . ', ' . $great . ', ' . $perfect . '], '
            . '["' . self::COLOR_BAD . '", "' . self::COLOR_OKAY . '", "' . self::COLOR_GOOD . '", "' . self::COLOR_GREAT . '"], '
            . '"' . self::COLOR_PERFECT . '")',
        ];
    }
}
