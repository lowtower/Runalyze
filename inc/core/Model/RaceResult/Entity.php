<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\RaceResult
 */

namespace Runalyze\Model\RaceResult;

use Runalyze\Model;

/**
 * RaceResult entity
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Model\RaceResult
 */
class Entity extends Model\Entity
{
    /**
     * Key: official_distance
     */
    public const string OFFICIAL_DISTANCE = 'official_distance';

    /**
     * Key: official_time
     */
    public const string OFFICIAL_TIME = 'official_time';

    /**
     * Key: offically_measured
     */
    public const string OFFICIALLY_MEASURED = 'officially_measured';

    /**
     * Key: name
     */
    public const string NAME = 'name';

    /**
     * Key: place_total
     */
    public const string PLACE_TOTAL = 'place_total';

    /**
     * Key: place_gender
     */
    public const string PLACE_GENDER = 'place_gender';

    /**
     * Key: place_ageclass
     */
    public const string PLACE_AGECLASS = 'place_ageclass';

    /**
     * Key: participants_total
     */
    public const string PARTICIPANTS_TOTAL = 'participants_total';

    /**
     * Key: participants_gender
     */
    public const string PARTICIPANTS_GENDER = 'participants_gender';

    /**
     * Key: participants_ageclass
     */
    public const string PARTICIPANTS_AGECLASS = 'participants_ageclass';

    /**
     * Key: activity id
     */
    public const string ACTIVITY_ID = 'activity_id';

    /**
     * Key: account id
     */
    public const string ACCOUNTID = 'accountid';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::OFFICIAL_DISTANCE,
            self::OFFICIAL_TIME,
            self::OFFICIALLY_MEASURED,
            self::NAME,
            self::PLACE_TOTAL,
            self::PLACE_GENDER,
            self::PLACE_AGECLASS,
            self::PARTICIPANTS_TOTAL,
            self::PARTICIPANTS_GENDER,
            self::PARTICIPANTS_AGECLASS,
            self::ACTIVITY_ID,
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
     * Ensure that numeric fields get numeric values
     */
    protected function ensureAllNumericValues()
    {
        $this->ensureNumericValue([
            self::OFFICIAL_DISTANCE,
            self::OFFICIAL_TIME,
            self::OFFICIALLY_MEASURED,
            self::ACTIVITY_ID,
        ]);
    }

    public function synchronize(): void
    {
        parent::synchronize();

        $this->ensureAllNullValues();
        $this->ensureAllNumericValues();
    }

    /**
     * Ensure that place/participants are null if empty
     */
    protected function ensureAllNullValues()
    {
        $this->ensureNullIfEmpty(self::PLACE_TOTAL, true);
        $this->ensureNullIfEmpty(self::PLACE_GENDER, true);
        $this->ensureNullIfEmpty(self::PLACE_AGECLASS, true);
        $this->ensureNullIfEmpty(self::PARTICIPANTS_TOTAL, true);
        $this->ensureNullIfEmpty(self::PARTICIPANTS_GENDER, true);
        $this->ensureNullIfEmpty(self::PARTICIPANTS_AGECLASS, true);
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::PLACE_TOTAL, self::PLACE_GENDER, self::PLACE_AGECLASS, self::PARTICIPANTS_TOTAL, self::PARTICIPANTS_GENDER, self::PARTICIPANTS_AGECLASS => true,
            default => false,
        };
    }

    public function setDefaultValuesFromActivity(Model\Activity\Entity $activity): void
    {
        $this->set(self::OFFICIAL_DISTANCE, $activity->distance());
        $this->set(self::OFFICIAL_TIME, $activity->duration());
        $this->set(self::NAME, $activity->title());

        if ($activity->isTrack()) {
            $this->set(self::OFFICIALLY_MEASURED, true);
        }
    }

    public function officialDistance(): float
    {
        return (float) $this->Data[self::OFFICIAL_DISTANCE];
    }

    /**
     * @return string
     */
    public function officialTime()
    {
        return $this->Data[self::OFFICIAL_TIME];
    }

    public function officiallyMeasured(): bool
    {
        return $this->Data[self::OFFICIALLY_MEASURED] == 1;
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    /**
     * @return string
     */
    public function placeTotal()
    {
        return $this->Data[self::PLACE_TOTAL];
    }

    /**
     * @return string
     */
    public function placeGender()
    {
        return $this->Data[self::PLACE_GENDER];
    }

    /**
     * @return string
     */
    public function placeAgeclass()
    {
        return $this->Data[self::PLACE_AGECLASS];
    }

    /**
     * participants total
     * @return string
     */
    public function participantsTotal()
    {
        return $this->Data[self::PARTICIPANTS_TOTAL];
    }

    /**
     * participants gender
     * @return string
     */
    public function participantsGender()
    {
        return $this->Data[self::PARTICIPANTS_GENDER];
    }

    /**
     * participants ageclass
     * @return string
     */
    public function participantsAgeclass()
    {
        return $this->Data[self::PARTICIPANTS_AGECLASS];
    }

    /**
     * @return string
     */
    public function activityId()
    {
        return $this->Data[self::ACTIVITY_ID];
    }

    /**
     * @return string
     */
    public function accountId()
    {
        return $this->Data[self::ACCOUNTID];
    }
}
