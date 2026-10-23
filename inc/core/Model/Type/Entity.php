<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Type
 */

namespace Runalyze\Model\Type;

use Runalyze\Model;

/**
 * Type entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Type
 */
class Entity extends Model\EntityWithID
{
    /**
     * Key: name
     */
    public const string NAME = 'name';

    /**
     * Key: short display
     */
    public const string ABBREVIATION = 'abbr';

    /**
     * Key: sport id
     */
    public const string SPORTID = 'sportid';

    /**
     * Key: short mode
     */
    public const string SHORT = 'short';

    /**
     * Key: avg. heart rate
     */
    public const string HR_AVG = 'hr_avg';

    /**
     * Key: quality session
     */
    public const string QUALITY_SESSION = 'quality_session';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::NAME,
            self::ABBREVIATION,
            self::SPORTID,
            self::SHORT,
            self::HR_AVG,
            self::QUALITY_SESSION,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return self::allDatabaseProperties();
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    public function abbreviation(): string
    {
        return $this->Data[self::ABBREVIATION];
    }

    public function sportid(): int
    {
        return $this->Data[self::SPORTID];
    }

    /**
     * Uses short mode?
     */
    public function usesShortMode(): bool
    {
        return $this->Data[self::SHORT] == 1;
    }

    /**
     * Inherits short mode from sport type?
     */
    public function inheritsShortMode(): bool
    {
        return $this->Data[self::SHORT] == 2;
    }

    /**
     * Avg. heart rate
     */
    public function hrAvg(): int
    {
        return $this->Data[self::HR_AVG];
    }

    /**
     * Is quality session?
     */
    public function isQualitySession(): bool
    {
        return $this->Data[self::QUALITY_SESSION] == 1;
    }
}
