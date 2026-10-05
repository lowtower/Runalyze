<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common;

interface UnitInterface
{
    public function getAppendix(): string;

    public function toBaseUnit(mixed $valueInThisUnit): mixed;

    public function fromBaseUnit(mixed $valueInBaseUnit): mixed;
}
