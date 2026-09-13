<?php

/**
 * This file contains class::Unit
 * @package Runalyze\Data\Cadence
 */

namespace Runalyze\Data\Cadence;

/**
 * Cadence unit
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Cadence
 */
class Unit
{
    /**
     * @var int
     */
    public const GENERAL = 1;

    /**
     * @var int
     */
    public const RUNNING = 2;

    /**
     * @return array<int, int>
     */
    public static function completeList(): array
    {
        return [
            self::GENERAL,
            self::RUNNING,
        ];
    }

    /**
     * Create cadence
     * @param int $identifier a class constant
     * @param int $value optional
     * @return \Runalyze\Data\Cadence\AbstractCadence
     */
    public function get($identifier, $value = 0)
    {
        return match ($identifier) {
            self::RUNNING => new Running($value),
            default => new General($value),
        };
    }
}
