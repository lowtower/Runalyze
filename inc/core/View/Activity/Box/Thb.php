<?php

declare(strict_types=1);

/**
 * This file contains class::RPE
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Calculation\Distribution\TrackdataAverages;
use Runalyze\Model\Trackdata;
use Runalyze\View\Activity\Context;

/**
 * Boxed value for Thb
 *
 * @author Michael Pohl
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class Thb extends AbstractBox
{
    public function __construct(Context $Context, $sensorIndex = 0)
    {
        if ($sensorIndex == 1) {
            $trackdataKey = Trackdata\Entity::THB_1;
            $label = _('THb') . ' (2)';
        } else {
            $trackdataKey = Trackdata\Entity::THB_0;
            $label = _('THb');
        }

        $value = new TrackdataAverages($Context->trackdata(), [$trackdataKey]);
        parent::__construct(
            (string) round($value->average($trackdataKey) / 100, 2),
            'g/dL',
            $label
        );
        $this->defineAsFloatingBlock('w50');
    }
}
