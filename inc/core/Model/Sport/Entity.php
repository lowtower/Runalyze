<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Sport
 */

namespace Runalyze\Model\Sport;

use Runalyze\Activity\PaceUnit\AbstractUnit;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Model;
use Runalyze\Profile\Sport\ProfileInterface;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\View\Icon\SportIcon;

/**
 * Sport entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Sport
 */
class Entity extends Model\EntityWithID implements ProfileInterface
{
    /**
     * Key: name
     */
    public const string NAME = 'name';

    /**
     * Key: img
     */
    public const string IMAGE = 'img';

    /**
     * Key: short display
     */
    public const string SHORT = 'short';

    /**
     * Key: kcal/h
     */
    public const string CALORIES_PER_HOUR = 'kcal';

    /**
     * Key: average heart rate
     */
    public const string HR_AVG = 'HFavg';

    /**
     * Key: pace unit
     */
    public const string PACE_UNIT = 'speed';

    /**
     * Key: has distances
     */
    public const string HAS_DISTANCES = 'distances';

    /**
     * Key: has power
     */
    public const string HAS_POWER = 'power';

    /**
     * Key: is outside
     */
    public const string IS_OUTSIDE = 'outside';

    /**
     * Key: lower bounds of the hr zones while the activity is done
     */
    public const string HR_ZONE_BOUNDS = 'hr_zone_bounds';

    /**
     * Key: id of main equipment (used for dataset)
     */
    public const string MAIN_EQUIPMENTTYPEID = 'main_equipmenttypeid';

    /**
     * Key: id of default sport type (used for multi import/automatic upload)
     */
    public const string DEFAULT_TYPEID = 'default_typeid';

    public const string IS_MAIN = 'is_main';

    public const string INTERNAL_SPORT_ID = 'internal_sport_id';

    /**
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::NAME,
            self::IMAGE,
            self::SHORT,
            self::CALORIES_PER_HOUR,
            self::HR_AVG,
            self::HAS_DISTANCES,
            self::PACE_UNIT,
            self::HAS_POWER,
            self::IS_OUTSIDE,
            self::HR_ZONE_BOUNDS,
            self::MAIN_EQUIPMENTTYPEID,
            self::DEFAULT_TYPEID,
            self::IS_MAIN,
            self::INTERNAL_SPORT_ID,
        ];
    }

    public function properties(): array
    {
        return self::allDatabaseProperties();
    }

    public function isArray(string $key): bool
    {
        return $key === self::HR_ZONE_BOUNDS;
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    public function getName(): string
    {
        return $this->Data[self::NAME];
    }

    public function getInternalProfileEnum(): int
    {
        return $this->Data[self::INTERNAL_SPORT_ID] ?: SportProfile::GENERIC;
    }

    /**
     * @codeCoverageIgnore
     */
    public function icon(): SportIcon
    {
        return new SportIcon($this->Data[self::IMAGE]);
    }

    public function getIconClass()
    {
        return $this->Data[self::IMAGE];
    }

    public function usesShortDisplay(): bool
    {
        return $this->Data[self::SHORT] == 1;
    }

    public function caloriesPerHour(): int
    {
        return (int) $this->Data[self::CALORIES_PER_HOUR];
    }

    public function getCaloriesPerHour(): int
    {
        return (int) $this->Data[self::CALORIES_PER_HOUR];
    }

    public function avgHR(): int
    {
        return (int) $this->Data[self::HR_AVG];
    }

    public function getAverageHeartRate(): int
    {
        return (int) $this->Data[self::HR_AVG];
    }

    public function legacyPaceUnit(): AbstractUnit
    {
        return new LegacyUnitConverter()->getLegacyPaceUnit($this->Data[self::PACE_UNIT]);
    }

    /**
     * @return int see \Runalyze\Activity\Pace or \Runalyze\Parameter\Application\PaceUnit
     */
    public function getLegacyPaceUnitEnum(): int
    {
        return new LegacyUnitConverter()->getLegacyPaceUnit($this->Data[self::PACE_UNIT], true);
    }

    public function getPaceUnitEnum(): int
    {
        return (int) $this->Data[self::PACE_UNIT];
    }

    public function hasDistances(): bool
    {
        return $this->Data[self::HAS_DISTANCES] == 1;
    }

    public function hasPower(): bool
    {
        return $this->Data[self::HAS_POWER] == 1;
    }

    public function isOutside(): bool
    {
        return $this->Data[self::IS_OUTSIDE] == 1;
    }

    /**
     * Average lower bounds of the hr zones
     * @return ?array [bpm]
     */
    public function hrZoneBounds(): ?array
    {
        return $this->Data[self::HR_ZONE_BOUNDS];
    }

    public function mainEquipmentTypeID(): int
    {
        return (int) $this->Data[self::MAIN_EQUIPMENTTYPEID];
    }

    public function defaultTypeID(): int
    {
        return (int) $this->Data[self::DEFAULT_TYPEID];
    }

    public function isMainSport(): bool
    {
        return $this->Data[self::IS_MAIN] == 1;
    }
}
