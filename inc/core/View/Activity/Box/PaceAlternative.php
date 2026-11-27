<?php

/**
 * This file contains class::PaceAlternative
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\SecondsPerKilometer;
use Runalyze\Metrics\Velocity\Unit\SecondsPerMile;

/**
 * Boxed value for pace alternative (instead of speed, if pace is already in a decimal unit)
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class PaceAlternative extends AbstractBox
{
    public function __construct(\Runalyze\View\Activity\Context $Context)
    {
        $unit = Configuration::General()->distanceUnitSystem()->isImperial()
            ? new SecondsPerMile()
            : new SecondsPerKilometer();
        $formatter = new PaceFormatter($unit);

        parent::__construct(
            $formatter->value($Context->dataview()->paceInSecondsPerKilometer()),
            $formatter->appendix(),
            __('avg.') . ' ' . __('Pace')
        );
    }
}
