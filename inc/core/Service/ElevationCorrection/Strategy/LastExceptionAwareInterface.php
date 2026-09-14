<?php

declare(strict_types=1);

namespace Runalyze\Service\ElevationCorrection\Strategy;

interface LastExceptionAwareInterface
{
    public function getLastException(): ?\Throwable;
}
