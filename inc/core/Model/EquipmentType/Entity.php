<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\EquipmentType
 */

namespace Runalyze\Model\EquipmentType;

use Runalyze\Model;

/**
 * Type entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\EquipmentType
 */
class Entity extends Model\EntityWithID
{
    /**
     * @var int enum
     */
    public const CHOICE_SINGLE = 0;

    /**
     * @var int enum
     */
    public const CHOICE_MULTIPLE = 1;

    /**
     * Key: name
     * @var string
     */
    public const NAME = 'name';

    /**
     * Key: input
     * @var string
     */
    public const INPUT = 'input';

    /**
     * Key: max distance
     * @var string
     */
    public const MAX_KM = 'max_km';

    /**
     * Key: max duration
     * @var string
     */
    public const MAX_TIME = 'max_time';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::NAME,
            self::INPUT,
            self::MAX_KM,
            self::MAX_TIME,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return static::allDatabaseProperties();
    }

    public function synchronize(): void
    {
        parent::synchronize();

        $this->ensureAllNumericValues();
    }

    /**
     * Ensure that numeric fields get numeric values
     */
    protected function ensureAllNumericValues()
    {
        $this->ensureNumericValue([
            self::INPUT,
            self::MAX_KM,
            self::MAX_TIME,
        ]);
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    /**
     * Allows multiple values
     */
    public function allowsMultipleValues(): bool
    {
        return $this->Data[self::INPUT] == self::CHOICE_MULTIPLE;
    }

    /**
     * Maximal distance
     * @return int [km]
     */
    public function maxDistance()
    {
        return $this->Data[self::MAX_KM];
    }

    /**
     * Is a maximal distance set?
     */
    public function hasMaxDistance(): bool
    {
        return $this->Data[self::MAX_KM] > 0;
    }

    /**
     * Maximal duration
     * @return int [s]
     */
    public function maxDuration(): int
    {
        return $this->Data[self::MAX_TIME];
    }

    /**
     * Is a maximal duration set?
     */
    public function hasMaxDuration(): bool
    {
        return $this->Data[self::MAX_TIME] > 0;
    }
}
