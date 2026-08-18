<?php

declare(strict_types=1);

namespace Runalyze\Profile\Mapping;

interface ToInternalMappingInterface
{
    /**
     * @param int|string $value
     * @return int|string
     */
    public function toInternal($value);
}
