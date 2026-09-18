<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Box;

use Runalyze\View\Activity\Context;

class Trimp extends AbstractBox
{
    public function __construct(Context $context)
    {
        parent::__construct(
            (string) $context->activity()
                ->trimp(),
            '',
            __('TRIMP'),
            '',
            'trimp'
        );
    }
}
