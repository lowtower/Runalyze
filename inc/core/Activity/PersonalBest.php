<?php

/**
 * This file contains class::PersonalBest
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use DB;
use PDO;

/**
 * Personal Bests
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class PersonalBest
{
    protected \PDO $PDO;

    /**
     * @var mixed
     */
    protected $Time = false;

    /**
     * @var mixed
     */
    protected $ActivityID;

    /**
     * @var mixed
     */
    protected $Timestamp;

    /**
     * @var array distance => pb
     */
    public static array $PBs = [];

    public static bool $USE_STATIC_CACHE = true;

    private static function getCacheKey(float $distance): string
    {
        // Always "1.00", "3.10", "5.00"
        return sprintf('%.2f', $distance);
    }

    /**
     * Activate use of static cache
     */
    public static function activateStaticCache(): void
    {
        self::$USE_STATIC_CACHE = true;
    }

    /**
     * Deactivate use of static cache
     */
    public static function deactivateStaticCache(): void
    {
        self::$USE_STATIC_CACHE = false;
        self::$PBs = [];
    }

    /**
     * Prefetch multiple PBs
     *
     * This method should be used to avoid multiple requests.
     * It requires the usage of the static cache.
     *
     * Usage:
     * <pre>PersonalBest::activateStaticCache();
     * PersonalBest::lookupDistances(array(3, 5, 10), 1, $PDO);
     * new PersonalBest(3);
     * ...</pre>
     *
     * @param array $distances distances in [km]
     */
    public static function lookupDistances(array $distances, int $sportid, ?PDO $pdo = null, bool $withDetails = false): int
    {
        foreach ($distances as $km) {
            self::$PBs[self::getCacheKey((float) $km)] = false;
        }

        $PDO = $pdo instanceof \PDO ? $pdo : DB::getInstance();
        $Data = $PDO->query(self::groupedQuery($distances, $sportid, $withDetails))->fetchAll();

        foreach ($Data as $result) {
            self::$PBs[self::getCacheKey((float) $result['official_distance'])] = $withDetails ? $result : $result['pb'];
        }

        return count($Data);
    }

    /**
     * Query to lookup distances
     * @param array $distances distances in [km]
     */
    private static function groupedQuery(array $distances, int $sportid, bool $withDetails = false): string
    {
        $distances = array_filter($distances, is_numeric(...));

        if ($withDetails) {
            return 'SELECT r.activity_id, tr.time, r.`official_distance`, MIN(r.`official_time`) as `pb` FROM `' . PREFIX . 'raceresult` as r '
                . 'LEFT JOIN `' . PREFIX . 'training` as tr ON tr.`id`= r.`activity_id` '
                . 'WHERE r.`accountid`=' . \SessionAccountHandler::getId() . ' '
                . 'AND r.`official_distance` IN(' . implode(',', $distances) . ') '
                . 'AND tr.`sportid`=' . $sportid . ' '
                . 'GROUP BY r.`official_distance`';
        }

        return 'SELECT r.activity_id, r.`official_distance`, MIN(r.`official_time`) as `pb` FROM `' . PREFIX . 'raceresult` as r '
                . 'LEFT JOIN `' . PREFIX . 'training` as tr ON tr.`id`= r.`activity_id` '
                . 'WHERE r.`accountid`=' . \SessionAccountHandler::getId() . ' '
                . 'AND r.`official_distance` IN(' . implode(',', $distances) . ') '
                . 'AND tr.`sportid`=' . $sportid . ' '
                . 'GROUP BY r.`official_distance`';
    }

    /**
     * @param float $Distance [km]
     */
    public function __construct(
        protected float $Distance,
        protected int $SportId,
        ?PDO $pdo = null,
        bool $autoLookup = true,
        bool $withDetails = false
    ) {
        $this->PDO = $pdo instanceof \PDO ? $pdo : DB::getInstance();

        if ($autoLookup) {
            if ($withDetails) {
                $this->lookupWithDetails();
            } else {
                $this->lookup();
            }
        }
    }

    /**
     * @return \Runalyze\Activity\PersonalBest this-reference
     */
    public function lookup(): self
    {
        $this->ActivityID = null;
        $this->Timestamp = null;
        $key = self::getCacheKey($this->Distance);

        if (self::$USE_STATIC_CACHE && isset(self::$PBs[$key])) {
            if (is_array(self::$PBs[$key])) {
                $this->Time = self::$PBs[$key]['pb'];
            } else {
                $this->Time = self::$PBs[$key];
            }
        } else {
            $this->Time = $this->PDO->query(
                'SELECT MIN(r.`official_time`), r.`activity_id` FROM `' . PREFIX . 'raceresult` as r '
                . 'LEFT JOIN `' . PREFIX . 'training` as tr ON tr.`id`= r.`activity_id`'
                . 'WHERE r.`accountid`=' . \SessionAccountHandler::getId() . '  '
                . 'AND r.`official_distance`="' . $this->Distance . '"'
                . 'AND tr.`sportid`=' . $this->SportId
            )->fetchColumn();

            if ($this->Time === null) {
                $this->Time = false;
            } elseif (self::$USE_STATIC_CACHE) {
                self::$PBs[$key] = $this->Time;
            }
        }

        return $this;
    }

    /**
     * @return \Runalyze\Activity\PersonalBest this-reference
     */
    public function lookupWithDetails(): self
    {
        $key = self::getCacheKey($this->Distance);
        if (self::$USE_STATIC_CACHE && isset(self::$PBs[$key]) && is_array(self::$PBs[$key])) {
            $Data = self::$PBs[$key];
        } else {
            $Data = $this->PDO->query(
                'SELECT r.`activity_id`, r.`official_time` as pb, tr.`time` FROM `' . PREFIX . 'raceresult` as r '
                . 'LEFT JOIN `' . PREFIX . 'training` as tr ON tr.`id` = r.`activity_id` '
                . 'WHERE r.`accountid`=' . \SessionAccountHandler::getId() . '  '
                . 'AND r.`official_distance`="' . $this->Distance . '" '
                . 'AND tr.`sportid`=' . $this->SportId . ' '
                . 'ORDER BY r.`official_time` ASC LIMIT 1'
            )->fetch();
        }

        if (! empty($Data)) {
            $this->ActivityID = $Data['activity_id'];
            $this->Timestamp = $Data['time'];
            $this->Time = $Data['pb'];

            if (self::$USE_STATIC_CACHE) {
                self::$PBs[$key] = $Data;
            }
        } else {
            $this->ActivityID = null;
            $this->Timestamp = null;
            $this->Time = false;
        }

        return $this;
    }

    /**
     * Does a personal best exist for this distance?
     */
    public function exists(): bool
    {
        return $this->Time !== false;
    }

    /**
     * Personal best in seconds
     * @return mixed may be false
     */
    public function seconds()
    {
        return $this->Time;
    }

    public function knowsActivity(): bool
    {
        return $this->ActivityID !== null;
    }

    /**
     * @return mixed may be null
     */
    public function activityId()
    {
        return $this->ActivityID;
    }

    /**
     * @return mixed may be null
     */
    public function timestamp()
    {
        return $this->Timestamp;
    }
}
