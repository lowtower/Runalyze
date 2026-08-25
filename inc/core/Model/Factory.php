<?php

/**
 * This file contains class::Factory
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

use Cache;
use DB;

/**
 * Model factory
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
class Factory
{
    /**
     * Database
     * @var \PDOforRunalyze
     */
    protected $DB;

    /**
     * Account ID
     * @var int
     */
    protected $AccountID;

    /**
     * Array with tablenames that should be cached statically
     * @var array
     */
    protected $UseStaticCacheAndFullFetch = [];

    /**
     * Static cache to not read file-cache every time
     * @var array
     */
    public static $StaticCache = [];

    /**
     * @param int $accountID [optional]
     */
    public function __construct($accountID = null)
    {
        $this->DB = DB::getInstance();
        $this->AccountID = $accountID;
        $this->UseStaticCacheAndFullFetch = [
            'sport' => true,
            'type' => true,
            'equipment' => true,
            'equipment_type' => true,
            'tag' => true,
        ];
    }

    /**
     * Has account id?
     */
    protected function hasAccountID(): bool
    {
        return $this->AccountID !== null;
    }

    /**
     * @param int $activityid
     */
    public function activity($activityid): \Runalyze\Model\Activity\Entity
    {
        return new Activity\Entity(
            $this->arrayByPK('training', $activityid)
        );
    }

    /**
     * @param int $activityid
     */
    public function trackdata($activityid): \Runalyze\Model\Trackdata\Entity
    {
        return new Trackdata\Entity(
            $this->arrayByPK('trackdata', $activityid)
        );
    }

    /**
     * @param int $activityid
     */
    public function swimdata($activityid): \Runalyze\Model\Swimdata\Entity
    {
        return new Swimdata\Entity(
            $this->arrayByPK('swimdata', $activityid)
        );
    }

    /**
     * @param int $routeid
     */
    public function route($routeid): \Runalyze\Model\Route\Entity
    {
        return new Route\Entity(
            $this->arrayByPK('route', $routeid)
        );
    }

    /**
     * @param int $activityid
     */
    public function hrv($activityid): \Runalyze\Model\HRV\Entity
    {
        return new HRV\Entity(
            $this->arrayByPK('hrv', $activityid)
        );
    }

    /**
     * @param int $activityid
     */
    public function raceResult($activityid): \Runalyze\Model\RaceResult\Entity
    {
        return new RaceResult\Entity(
            $this->arrayByPK('raceresult', $activityid)
        );
    }

    /**
     * @param int $typeid
     */
    public function type($typeid): \Runalyze\Model\Type\Entity
    {
        return new Type\Entity(
            $this->arrayByPK('type', $typeid)
        );
    }

    /**
     * All type objects
     * @return \Runalyze\Model\Type\Entity[]
     */
    public function allTypes(): array
    {
        return $this->allObjects('type', function ($data): \Runalyze\Model\Type\Entity {
            return new Type\Entity($data);
        });
    }

    /**
     * @param int $sportid
     * @return \Runalyze\Model\Type\Entity[]
     */
    public function typeForSport($sportid): array
    {
        $Types = $this->allTypes();
        $SportTypes = [];

        foreach ($Types as $Type) {
            if ($Type->sportid() == $sportid) {
                $SportTypes[] = $Type;
            }
        }

        return $SportTypes;
    }

    /**
     * @param int $sportid
     */
    public function sport($sportid): \Runalyze\Model\Sport\Entity
    {
        return new Sport\Entity(
            $this->arrayByPK('sport', $sportid)
        );
    }

    /**
     * All sport objects
     * @return \Runalyze\Model\Sport\Entity[]
     */
    public function allSports(): array
    {
        return $this->allObjects('sport', function ($data): \Runalyze\Model\Sport\Entity {
            return new Sport\Entity($data);
        });
    }

    /**
     * @param int $equipmentTypeid
     */
    public function equipmentType($equipmentTypeid): \Runalyze\Model\EquipmentType\Entity
    {
        return new EquipmentType\Entity(
            $this->arrayByPK('equipment_type', $equipmentTypeid)
        );
    }

    /**
     * @param int $equipmentid
     */
    public function equipment($equipmentid): \Runalyze\Model\Equipment\Entity
    {
        return new Equipment\Entity(
            $this->arrayByPK('equipment', $equipmentid)
        );
    }

    /**
     * @param int $activityid
     * @param bool $onlyIDs [optional]
     * @return int[]|\Runalyze\Model\Equipment\Entity[]
     */
    public function equipmentForActivity($activityid, $onlyIDs = false)
    {
        $Equipment = [];

        $IDs = $this->DB->query('SELECT `equipmentid` FROM `' . PREFIX . 'activity_equipment` WHERE `activityid`="' . $activityid . '"')->fetchAll(\PDO::FETCH_COLUMN);

        if ($onlyIDs) {
            return $IDs;
        }

        foreach ($IDs as $id) {
            $Equipment[] = $this->equipment($id);
        }

        return $Equipment;
    }

    /**
     * @param int $tagid
     */
    public function tag($tagid): \Runalyze\Model\Tag\Entity
    {
        return new Tag\Entity(
            $this->arrayByPK('tag', $tagid)
        );
    }

    /**
     * All tag objects
     * @return \Runalyze\Model\Tag\Entity[]
     */
    public function allTags(): array
    {
        return $this->allObjects('tag', function ($data): \Runalyze\Model\Tag\Entity {
            return new Tag\Entity($data);
        });
    }

    /**
     * @param int $activityid
     * @param bool $onlyIDs [optional]
     * @return int[]|\Runalyze\Model\Tag\Entity[]
     */
    public function tagForActivity($activityid, $onlyIDs = false)
    {
        $Tag = [];

        $IDs = $this->DB->query('SELECT `tagid` FROM `' . PREFIX . 'activity_tag` WHERE `activityid`="' . $activityid . '"')->fetchAll(\PDO::FETCH_COLUMN);

        if ($onlyIDs) {
            return $IDs;
        }

        foreach ($IDs as $id) {
            $Tag[] = $this->tag($id);
        }

        return $Tag;
    }

    /**
     * Array by primary key
     * @param string $tablename
     * @param int $id
     * @param int $cachetime [optional]
     * @return array
     */
    protected function arrayByPK($tablename, $id, $cachetime = 3600)
    {
        if (! $cachetime) {
            return $this->fetch($tablename, $id);
        }

        if (isset($this->UseStaticCacheAndFullFetch[$tablename])) {
            return $this->arrayByPKfromStaticCache($tablename, $id);
        }

        return $this->arrayByPKfromCache($tablename, $id, $cachetime);
    }

    /**
     * Array by primary key from static cache
     * @param string $tablename
     * @param int $id
     * @return array
     */
    protected function arrayByPKfromStaticCache($tablename, $id)
    {
        if (! isset(self::$StaticCache[$tablename])) {
            $this->fetchStaticCache($tablename);
        }

        return self::$StaticCache[$tablename][$id] ?? [];
    }

    /**
     * @param string $tablename
     */
    protected function fetchStaticCache($tablename)
    {
        $pk = $this->primaryKey($tablename);
        $allData = $this->fetch($tablename, 0, true);
        self::$StaticCache[$tablename] = [];

        foreach ($allData as $data) {
            self::$StaticCache[$tablename][$data[$pk]] = $data;
        }
    }

    /**
     * Array by primary key from cache class
     * @param string $tablename
     * @param int $id
     * @param int $cachetime [optional]
     * @return array
     */
    protected function arrayByPKfromCache($tablename, $id, $cachetime)
    {
        $data = Cache::get($tablename . $id);

        if ($data === null) {
            $data = $this->fetch($tablename, $id);

            Cache::set($tablename . $id, $data, $cachetime);
        }

        return $data;
    }

    /**
     * @param string $tablename
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function allData($tablename)
    {
        if (! isset($this->UseStaticCacheAndFullFetch[$tablename])) {
            throw new \InvalidArgumentException('The table "' . $tablename . '" does not provide a full fetch');
        }

        if (! isset(self::$StaticCache[$tablename])) {
            $this->fetchStaticCache($tablename);
        }

        return self::$StaticCache[$tablename];
    }

    /**
     * @param string $tablename
     * @return \Runalyze\Model\Entity[]
     */
    public function allObjects($tablename, \Closure $constructor): array
    {
        $allObjects = [];
        $allData = $this->allData($tablename);

        foreach ($allData as $data) {
            $allObjects[] = $constructor($data);
        }

        return $allObjects;
    }

    /**
     * @param string|bool $tablename
     * @param int|bool $id can be empty if the table uses a full fetch and static cache
     * @throws \InvalidArgumentException
     */
    public function clearCache($tablename = false, $id = false)
    {
        if ($tablename === false) {
            foreach (array_keys($this->UseStaticCacheAndFullFetch) as $tablename) {
                $this->clearCache($tablename);
            }

            return;
        }

        if (isset($this->UseStaticCacheAndFullFetch[$tablename])) {
            if (isset(self::$StaticCache[$tablename])) {
                unset(self::$StaticCache[$tablename]);
            }
        } elseif ($id) {
            Cache::delete($tablename . $id);
        } else {
            throw new \InvalidArgumentException('Argument $id must be set, the table "' . $tablename . '" does not use a static cache.');
        }
    }

    /**
     * Fetch data
     * @param string $tablename
     * @param int $id
     * @param bool $fullFetch
     * @return array
     */
    protected function fetch($tablename, $id, $fullFetch = false)
    {
        $field = $this->primaryKey($tablename);
        $AndAccountID = $this->hasAccountID() && $this->tableHasAccountid($tablename) ? 'AND `accountid`=' . (int) $this->AccountID : '';

        if ($fullFetch) {
            $result = $this->DB->query('SELECT * FROM `' . PREFIX . $tablename . '` WHERE 1 ' . $AndAccountID . ' ' . $this->orderBy($tablename))->fetchAll();
        } else {
            $result = $this->DB->query('SELECT * FROM `' . PREFIX . $tablename . '` WHERE `' . $field . '`=' . (int) $id . ' ' . $AndAccountID . ' LIMIT 1')->fetch();
        }

        if (! is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * Get primary key
     * @param string $tablename
     */
    protected function primaryKey($tablename): string
    {
        switch ($tablename) {
            case 'trackdata':
            case 'hrv':
            case 'swimdata':
                return 'activityid';
            case 'raceresult':
                return 'activity_id';
        }

        return 'id';
    }

    /**
     * Has the table a column 'accountid'?
     * @param string $tablename
     */
    protected function tableHasAccountid($tablename): bool
    {
        switch ($tablename) {
            case 'account':
            case 'plugin_conf':
                return false;
        }

        return true;
    }

    /**
     * Order for full fetch
     * @param string $tablename
     */
    protected function orderBy($tablename): string
    {
        switch ($tablename) {
            case 'sport':
                return \Runalyze\Configuration::ActivityForm()->orderSports()->asQuery();
            case 'type':
                return \Runalyze\Configuration::ActivityForm()->orderTypes()->asQuery();
            case 'equipment':
                return \Runalyze\Configuration::ActivityForm()->orderEquipment()->asQuery();
        }

        return '';
    }
}
