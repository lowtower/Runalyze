<?php

/**
 * This file contains class::Speed
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;
use Runalyze\Metrics\Velocity\Unit\MilesPerHour;

/**
 * Boxed value for speed
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class Speed extends AbstractBox
{
    public function __construct(\Runalyze\View\Activity\Context $Context)
    {
        $unit = Configuration::General()->distanceUnitSystem()->isImperial()
            ? new MilesPerHour()
            : new KilometerPerHour();
        $formatter = new PaceFormatter($unit);

        parent::__construct(
            $formatter->value($Context->dataview()->paceInSecondsPerKilometer()),
            $formatter->appendix(),
            __('avg.') . ' ' . __('Speed')
        );
    }
}
