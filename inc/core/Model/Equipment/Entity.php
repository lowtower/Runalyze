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
     */
    public const string NAME = 'name';

    /**
     * Key: typeid
     */
    public const string TYPEID = 'typeid';

    /**
     * Key: notes
     */
    public const string NOTES = 'notes';

    /**
     * Key: distance
     */
    public const string DISTANCE = 'distance';

    /**
     * Key: duration
     */
    public const string TIME = 'time';

    /**
     * Key: additional distance
     */
    public const string ADDITIONAL_KM = 'additional_km';

    /**
     * Key: start date
     */
    public const string DATE_START = 'date_start';

    /**
     * Key: end date
     */
    public const string DATE_END = 'date_end';

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
        return self::allDatabaseProperties();
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
