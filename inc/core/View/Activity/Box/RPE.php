<?php

/**
 * This file contains class::RPE
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\View\Activity\Context;

/**
 * Boxed value for RPE
 *
 * @author Michael Pohl
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class RPE extends AbstractBox
{
    public function __construct(Context $Context)
    {

        parent::__construct(
            $Context->dataview()
                ->rpe(true),
            '',
            __('RPE'),
            '',
            'rpe'
        );
        $this->defineAsFloatingBlock('w50');
    }
}
