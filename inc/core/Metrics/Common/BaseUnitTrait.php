<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common;

trait BaseUnitTrait
{
    public function toBaseUnit(mixed $valueInThisUnit): mixed
    {
        return $valueInThisUnit;
    }

    public function fromBaseUnit(mixed $valueInBaseUnit): mixed
    {
        return $valueInBaseUnit;
    }
}
