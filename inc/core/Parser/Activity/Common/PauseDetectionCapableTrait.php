<?php

namespace Runalyze\Parser\Activity\Common;

trait PauseDetectionCapableTrait
{
    protected bool $DetectPauses = true;

    public function activatePauseDetection(bool $flag = true)
    {
        $this->DetectPauses = $flag;
    }
}
