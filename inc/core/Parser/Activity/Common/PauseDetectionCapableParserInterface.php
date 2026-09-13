<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common;

interface PauseDetectionCapableParserInterface
{
    public function activatePauseDetection(bool $flag = true);
}
