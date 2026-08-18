<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class VO2maxIcon extends AbstractKey
{
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::VO2MAX_ICON;
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
        return __('Effective VO<sub>2</sub>max icon');
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
        $text = __(
            'Each VO<sub>2</sub>max value of an activity is marked with an arrow '.
                'to show if the value is (much) higher than your current shape, '.
                'equal to it or (much) lower:'
        ).' ';

        $Icon = new \Runalyze\View\Icon\EffectiveVO2maxIcon();
        $Icon->setUp();
        $text .= $Icon->code();
        $Icon->setUpHalf();
        $text .= $Icon->code();
        $Icon->setRight();
        $text .= $Icon->code();
        $Icon->setDownHalf();
        $text .= $Icon->code();
        $Icon->setDown();

        return $text . $Icon->code();
    }

    /**
     * Get string to display this dataset value
     *
     * @return string
     */
    public function stringFor(Context $context)
    {
        if ($context->isRunning() && $context->dataview()->usedVO2maxValue() > 0) {
            if (! \Request::isOnSharedPage() && $context->activity()->id() > 0) {
                return '<a class="window" href="activity/' . $context->activity()->id() . '/vo2max-info">' . $context->dataview()->effectiveVO2maxIcon() . '</a>';
            }

            return $context->dataview()
                ->effectiveVO2maxIcon();
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
