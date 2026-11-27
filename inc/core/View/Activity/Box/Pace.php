<?php

declare(strict_types=1);

/**
 * This file contains class::Pace
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;

/**
 * Boxed value for pace
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class Pace extends AbstractBox
{
    public function __construct(\Runalyze\View\Activity\Context $Context)
    {
        $formatter = new PaceFormatter($Context->sport()->getSpeedUnit());

        parent::__construct(
            $formatter->value($Context->dataview()->paceInSecondsPerKilometer()),
            $formatter->appendix(),
            __('avg.') . ' ' . ($formatter->isTimeFormat() ? __('Pace') : __('Speed'))
        );
    }
}
