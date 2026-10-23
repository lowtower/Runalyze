<?php

/**
 * This file contains class::Distance
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;
use Runalyze\View\Stresscolor;

/**
 * Dataset key: Distance
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Distance extends AbstractKey
{
    public const string KEY_DISTANCE_COMPARISON = 'distance_to_compare_to';

    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::DISTANCE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return 'distance';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Distance');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if ($context->hasData(self::KEY_DISTANCE_COMPARISON)) {
            return $context->dataview()
                ->distance() .
                $this->distanceComparison($context->activity()->distance(), $context->data(self::KEY_DISTANCE_COMPARISON));
        }

        return $context->dataview()
            ->distance();
    }

    /**
     * Compare distance to last set
     * @param float $currentDistance [km]
     * @param float $previousDistance [km]
     */
    protected function distanceComparison(float $currentDistance, float $previousDistance): string
    {
        $Percentage = $this->distanceComparisonPercentage($currentDistance, $previousDistance);
        $String = ($Percentage !== 0) ? sprintf('%+d', $Percentage) . '&nbsp;&#37;' : '-';

        $Stress = new Stresscolor($Percentage);
        $Stress->scale(0, 30);

        return ' <small style="display:inline-block;width:55px;color:#' . $Stress->rgb() . '">' . $String . '</small>';
    }

    /**
     * Get percentage of last distance
     * @param float $currentDistance [km]
     * @param float $previousDistance [km]
     */
    protected function distanceComparisonPercentage(float $currentDistance, float $previousDistance): int
    {
        if ($previousDistance == 0) {
            return 0;
        }

        return (int) round(100 * ($currentDistance - $previousDistance) / $previousDistance, 1);
    }

    /**
     * @return int see \Runalyze\Dataset\SummaryMode for enum
     */
    public function summaryMode(): int
    {
        return SummaryMode::SUM;
    }
}
