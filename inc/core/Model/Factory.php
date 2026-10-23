<?php

namespace Runalyze\Model;

use Cache;
use Closure;
use DB;
use InvalidArgumentException;
use PDO;
use PDOforRunalyze;
use Runalyze\Configuration;

/**
 * Model factory
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
class Factory
{
    protected PDOforRunalyze $DB;

    /**
     * Array with tablenames that should be cached statically
     */
    protected array $UseStaticCacheAndFullFetch;

    /**
     * Static cache to not read file-cache every time
     */
    public static array $StaticCache = [];

    public function __construct(
        protected ?int $AccountID = null
    ) {
        $this->DB = DB::getInstance();
        $this->UseStaticCacheAndFullFetch = [
            'sport' => true,
            'type' => true,
            'equipment' => true,
            'equipment_type' => true,
            'tag' => true,
        ];
    }

    protected function hasAccountID(): bool
    {
        return $this->AccountID !== null;
    }

    public function activity(int $activityid): Activity\Entity
    {
        return new Activity\Entity(
            $this->arrayByPK('training', $activityid)
        );
    }

    public function trackdata(int $activityid): Trackdata\Entity
    {
        return new Trackdata\Entity(
            $this->arrayByPK('trackdata', $activityid)
        );
    }

    public function swimdata(int $activityid): Swimdata\Entity
    {
        return new Swimdata\Entity(
            $this->arrayByPK('swimdata', $activityid)
        );
    }

    public function route(int $routeid): Route\Entity
    {
        return new Route\Entity(
            $this->arrayByPK('route', $routeid)
        );
    }

    public function hrv(int $activityid): HRV\Entity
    {
        return new HRV\Entity(
            $this->arrayByPK('hrv', $activityid)
        );
    }

    public function raceResult(int $activityid): RaceResult\Entity
    {
        return new RaceResult\Entity(
            $this->arrayByPK('raceresult', $activityid)
        );
    }

    public function type(int $typeid): Type\Entity
    {
        return new Type\Entity(
            $this->arrayByPK('type', $typeid)
        );
    }

    /**
     * @return Type\Entity[]
     */
    public function allTypes(): array
    {
        return $this->allObjects('type', fn ($data): Type\Entity => new Type\Entity($data));
    }

    /**
     * @return Type\Entity[]
     */
    public function typeForSport(int $sportid): array
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

    public function sport(int $sportid): Sport\Entity
    {
        return new Sport\Entity(
            $this->arrayByPK('sport', $sportid)
        );
    }

    /**
     * @return Sport\Entity[]
     */
    public function allSports(): array
    {
        return $this->allObjects('sport', fn ($data): Sport\Entity => new Sport\Entity($data));
    }

    public function equipmentType(int $equipmentTypeid): EquipmentType\Entity
    {
        return new EquipmentType\Entity(
            $this->arrayByPK('equipment_type', $equipmentTypeid)
        );
    }

    public function equipment(int $equipmentid): Equipment\Entity
    {
        return new Equipment\Entity(
            $this->arrayByPK('equipment', $equipmentid)
        );
    }

    /**
     * @return int[]|Equipment\Entity[]
     */
    public function equipmentForActivity(int $activityid, bool $onlyIDs = false): array
    {
        $Equipment = [];

        $IDs = $this->DB->query('SELECT `equipmentid` FROM `' . PREFIX . 'activity_equipment` WHERE `activityid`="' . $activityid . '"')->fetchAll(PDO::FETCH_COLUMN);

        if ($onlyIDs) {
            return $IDs;
        }

        foreach ($IDs as $id) {
            $Equipment[] = $this->equipment((int) $id);
        }

        return $Equipment;
    }

    public function tag(int $tagid): Tag\Entity
    {
        return new Tag\Entity(
            $this->arrayByPK('tag', $tagid)
        );
    }

    /**
     * @return Tag\Entity[]
     */
    public function allTags(): array
    {
        return $this->allObjects('tag', fn ($data): Tag\Entity => new Tag\Entity($data));
    }

    /**
     * @return int[]|Tag\Entity[]
     */
    public function tagForActivity(int $activityid, bool $onlyIDs = false): array
    {
        $Tag = [];

        $IDs = $this->DB->query('SELECT `tagid` FROM `' . PREFIX . 'activity_tag` WHERE `activityid`="' . $activityid . '"')->fetchAll(PDO::FETCH_COLUMN);

        if ($onlyIDs) {
            return $IDs;
        }

        foreach ($IDs as $id) {
            $Tag[] = $this->tag($id);
        }

        return $Tag;
    }

    protected function arrayByPK(string $tablename, int $id, int $cachetime = 3600): array
    {
        if (! $cachetime) {
            return $this->fetch($tablename, $id);
        }

        if (isset($this->UseStaticCacheAndFullFetch[$tablename])) {
            return $this->arrayByPKfromStaticCache($tablename, $id);
        }

        return $this->arrayByPKfromCache($tablename, $id, $cachetime);
    }

    protected function arrayByPKfromStaticCache(string $tablename, int $id): array
    {
        if (! isset(self::$StaticCache[$tablename])) {
            $this->fetchStaticCache($tablename);
        }

        return self::$StaticCache[$tablename][$id] ?? [];
    }

    protected function fetchStaticCache(string $tablename): void
    {
        $pk = $this->primaryKey($tablename);
        $allData = $this->fetch($tablename, 0, true);
        self::$StaticCache[$tablename] = [];

        foreach ($allData as $data) {
            self::$StaticCache[$tablename][$data[$pk]] = $data;
        }
    }

    protected function arrayByPKfromCache(string $tablename, int $id, int $cachetime): array
    {
        $data = Cache::get($tablename . $id);

        if ($data === null) {
            $data = $this->fetch($tablename, $id);

            Cache::set($tablename . $id, $data, $cachetime);
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function allData(string $tablename): array
    {
        if (! isset($this->UseStaticCacheAndFullFetch[$tablename])) {
            throw new InvalidArgumentException('The table "' . $tablename . '" does not provide a full fetch');
        }

        if (! isset(self::$StaticCache[$tablename])) {
            $this->fetchStaticCache($tablename);
        }

        return self::$StaticCache[$tablename];
    }

    /**
     * @template T of Entity
     * @param Closure(array<string, mixed>): T $constructor
     * @return T[]
     */
    public function allObjects(string $tablename, Closure $constructor): array
    {
        $allObjects = [];
        $allData = $this->allData($tablename);

        foreach ($allData as $data) {
            $allObjects[] = $constructor($data);
        }

        return $allObjects;
    }

    /**
     * @param string|false $tablename false clears the cache for every full-fetch/static-cache table
     * @param int|false $id can be omitted (false) if the table uses a full fetch and static cache
     * @throws InvalidArgumentException
     */
    public function clearCache(string|false $tablename = false, int|false $id = false): void
    {
        if ($tablename === false) {
            foreach (array_keys($this->UseStaticCacheAndFullFetch) as $tablename_cached) {
                $this->clearCache($tablename_cached);
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
            throw new InvalidArgumentException('Argument $id must be set, the table "' . $tablename . '" does not use a static cache.');
        }
    }

    protected function fetch(string $tablename, int $id, bool $fullFetch = false): array
    {
        $field = $this->primaryKey($tablename);
        $AndAccountID = $this->hasAccountID() && $this->tableHasAccountid($tablename) ? 'AND `accountid`=' . $this->AccountID : '';

        if ($fullFetch) {
            $result = $this->DB->query('SELECT * FROM `' . PREFIX . $tablename . '` WHERE 1 ' . $AndAccountID . ' ' . $this->orderBy($tablename))->fetchAll();
        } else {
            $result = $this->DB->query('SELECT * FROM `' . PREFIX . $tablename . '` WHERE `' . $field . '`=' . $id . ' ' . $AndAccountID . ' LIMIT 1')->fetch();
        }

        if (! is_array($result)) {
            return [];
        }

        return $result;
    }

    protected function primaryKey(string $tablename): string
    {
        return match ($tablename) {
            'trackdata', 'hrv', 'swimdata' => 'activityid',
            'raceresult' => 'activity_id',
            default => 'id',
        };
    }

    /**
     * Has the table a column 'accountid'?
     */
    protected function tableHasAccountid(string $tablename): bool
    {
        return match ($tablename) {
            'account', 'plugin_conf' => false,
            default => true,
        };
    }

    /**
     * Order for full fetch
     */
    protected function orderBy(string $tablename): string
    {
        return match ($tablename) {
            'sport' => Configuration::ActivityForm()->orderSports()->asQuery(),
            'type' => Configuration::ActivityForm()->orderTypes()->asQuery(),
            'equipment' => Configuration::ActivityForm()->orderEquipment()->asQuery(),
            default => '',
        };
    }
}
