<?php

declare(strict_types=1);

/**
 * This file contains class::GradeAdjustedPace
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Calculation\Activity\GradeAdjustedPaceCalculator;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Boxed value for the grade adjusted pace (=GAP)
 *
 * @author codeproducer
 * @package Runalyze\View\Activity\Box
 */
class GradeAdjustedPace extends AbstractBox
{
    public function __construct(\Runalyze\View\Activity\Context $context)
    {
        $formatter = new PaceFormatter($context->sport()->getSpeedUnit());
        $adjustedTime = new GradeAdjustedPaceCalculator($context)->getAdjustedTime();
        $pace = PaceFormatter::secondsPerKilometer($adjustedTime, $context->activity()->distance() ?? 0.0);

        parent::__construct(
            $formatter->value($pace),
            $formatter->appendix(),
            __('avg.') . ' ' . ($formatter->isTimeFormat() ? 'GAP' : 'GAP Speed')
        );
    }

    public static function hasPace(\Runalyze\View\Activity\Context $context): bool
    {
        return $context->sport()
            ->getInternalProfileEnum() === SportProfile::RUNNING
            && $context->hasRoute() && $context->route()
                ->hasElevations()
            && $context->hasTrackdata() && $context->trackdata()
                ->has(Trackdata::DISTANCE);
    }
}
