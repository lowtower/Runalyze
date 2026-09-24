<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Scale;

interface ScaleInterface
{
    public function transform(float $input): float;
}
