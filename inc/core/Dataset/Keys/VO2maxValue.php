<?php

declare(strict_types=1);

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class VO2maxValue extends AbstractKey
{
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::VO2MAX_VALUE;
    }

    public function column(): string
    {
        if (\Runalyze\Configuration::VO2max()->useElevationCorrection()) {
            return 'vo2max_with_elevation';
        }

        return 'vo2max';
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Effective VO<sub>2</sub>max');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return 'VO<sub>2</sub>max';
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __(
            'Estimated VO<sub>2</sub>max based on pace and heart rate. ' .
            'The value is slightly transparent if it is not used for your VO<sub>2</sub>max shape.'
        );
    }

    /**
     * Get string to display this dataset value
     *
     * @return string
     */
    public function stringFor(Context $context)
    {
        if ($context->isRunning() && $context->dataview()->usedVO2maxValue() > 0) {
            if (! \Request::isOnSharedPage() && $context->activity()->id() > 0 ) {
                if (! $context->activity()->usesVO2max()) {
                    return '<a class="window unimportant" href="activity/' . $context->activity()->id() . '/vo2max-info">' . $context->dataview()->vo2max()->value() . '</a>';
                }

                return '<a class="window" href="activity/' . $context->activity()->id() . '/vo2max-info">' . $context->dataview()->vo2max()->value() . '</a>';
            }

            return $context->dataview()
                ->vo2max()
                ->value();
        }

        return '';
    }

    /**
     * @return int see \Runalyze\Dataset\SummaryMode for enum
     */
    public function summaryMode(): int
    {
        return SummaryMode::VO2MAX;
    }
}
