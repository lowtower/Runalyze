<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Equipment
 */

namespace Runalyze\Model\Equipment;

use Runalyze\Model;

/**
 * Type entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Equipment
 */
class Entity extends Model\EntityWithID
{
    /**
     * Key: name
     * @var string
     */
    public const NAME = 'name';

    /**
     * Key: typeid
     * @var string
     */
    public const TYPEID = 'typeid';

    /**
     * Key: notes
     * @var string
     */
    public const NOTES = 'notes';

    /**
     * Key: distance
     * @var string
     */
    public const DISTANCE = 'distance';

    /**
     * Key: duration
     * @var string
     */
    public const TIME = 'time';

    /**
     * Key: additional distance
     * @var string
     */
    public const ADDITIONAL_KM = 'additional_km';

    /**
     * Key: start date
     * @var string
     */
    public const DATE_START = 'date_start';

    /**
     * Key: end date
     * @var string
     */
    public const DATE_END = 'date_end';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::NAME,
            self::TYPEID,
            self::NOTES,
            self::DISTANCE,
            self::TIME,
            self::ADDITIONAL_KM,
            self::DATE_START,
            self::DATE_END,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return static::allDatabaseProperties();
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::DATE_START, self::DATE_END => true,
            default => false,
        };
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
            self::DISTANCE,
            self::TIME,
            self::ADDITIONAL_KM,
        ]);
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    /**
     * Equipment type id
     */
    public function typeid(): int
    {
        return (int) $this->Data[self::TYPEID];
    }

    /**
     * Notes
     */
    public function notes(): string
    {
        return $this->Data[self::NOTES];
    }

    /**
     * @return float  [km]
     */
    public function distance(): float
    {
        return (float) $this->Data[self::DISTANCE];
    }

    /**
     * @return float [s]
     */
    public function duration(): float
    {
        return (float) $this->Data[self::TIME];
    }

    /**
     * @return float [km]
     */
    public function additionalDistance(): float
    {
        return (float) $this->Data[self::ADDITIONAL_KM];
    }

    /**
     * @return float [km]
     */
    public function totalDistance(): float
    {
        return (float) $this->Data[self::DISTANCE] + $this->Data[self::ADDITIONAL_KM];
    }

    /**
     * @return string Y-m-d
     */
    public function startDate(): ?string
    {
        return $this->Data[self::DATE_START];
    }

    /**
     * Has this equipment a start date?
     */
    public function hasStartDate(): bool
    {
        return $this->Data[self::DATE_START] !== null;
    }

    /**
     * @return string Y-m-d
     */
    public function endDate(): ?string
    {
        return $this->Data[self::DATE_END];
    }

    /**
     * Is this equipment still in use?
     */
    public function isInUse(): bool
    {
        return $this->Data[self::DATE_END] === null;
    }
}
