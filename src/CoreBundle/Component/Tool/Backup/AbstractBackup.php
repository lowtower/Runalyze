<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

use Runalyze\Util\File\GZipWriter;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractBackup
{
    protected GZipWriter $Writer;

    protected int $AccountID;

    protected \PDO $PDO;

    protected string $Prefix;

    protected string $RunalyzeVersion;

    protected string $Filename;

    public function __construct(string $filename, int $accountid, \PDO $databaseConnection, string $databaseTablePrefix, string $runalyzeVersion)
    {
        $this->Writer = new GZipWriter(md5($filename));
        $this->AccountID = $accountid;
        $this->PDO = $databaseConnection;
        $this->Prefix = $databaseTablePrefix;
        $this->RunalyzeVersion = $runalyzeVersion;
        $this->Filename = $filename;
    }

    final public function run()
    {
        if ($this->PDO instanceof \PDOforRunalyze) {
            $this->PDO->stopAddingAccountID();
        }

        $this->startBackup();

        // REMINDER: think about required order, e.g.
        // - plugin before plugin_conf
        // - sport/type/route before training
        // - training before trackdata/swimdata/hrv
        // - equipment_Type before equipment_sport before equipment before activity_equipment
        // - tag before activity_tag
        $Tables = [
            $this->Prefix . 'account',
            $this->Prefix . 'conf',
            $this->Prefix . 'dataset',
            $this->Prefix . 'plugin',
            $this->Prefix . 'plugin_conf',
            $this->Prefix . 'sport',
            $this->Prefix . 'type',
            $this->Prefix . 'user',
            $this->Prefix . 'route',
            $this->Prefix . 'training',
            $this->Prefix . 'trackdata',
            $this->Prefix . 'swimdata',
            $this->Prefix . 'hrv',
            $this->Prefix . 'equipment_type',
            $this->Prefix . 'equipment_sport',
            $this->Prefix . 'equipment',
            $this->Prefix . 'equipment_to_sport',
            $this->Prefix . 'activity_equipment',
            $this->Prefix . 'tag',
            $this->Prefix . 'activity_tag',
            $this->Prefix . 'raceresult',
        ];

        foreach ($Tables as $TableName) {
            $this->saveTableRows($TableName);
        }

        $this->finishBackup();

        if ($this->PDO instanceof \PDOforRunalyze) {
            $this->PDO->startAddingAccountID();
        }

        $this->Writer->finish();
        $fs = new Filesystem();
        $fs->rename(md5($this->Filename), $this->Filename);
    }

    protected function startBackup()
    {
    }

    protected function finishBackup()
    {
    }

    private function saveTableRows(string $tableName)
    {
        $columnInfo = $this->PDO->query('SHOW COLUMNS FROM ' . $tableName)->fetchAll();

        $query = 'SELECT * FROM `' . $tableName . '`';
        $ids = $this->addConditionToQuery($query, $tableName);

        $this->startTableRows($tableName);

        if (! is_array($ids) || $ids !== []) {
            $statement = $this->PDO->query($query);
            $this->saveRowsFromStatement($tableName, $columnInfo, $statement);
        }

        $this->finishTableRows();
    }

    /**
     * @return bool|array
     */
    private function addConditionToQuery(string &$query, string $tableName)
    {
        $ids = false;

        if ($tableName === $this->Prefix . 'account') {
            $query .= ' WHERE `id`=' . $this->AccountID . ' LIMIT 1';
        } elseif ($tableName === $this->Prefix . 'plugin_conf') {
            $ids = $this->fetchPluginIDs();
            $query .= ' WHERE `pluginid` IN(' . implode(',', $ids) . ')';
        } elseif ($tableName === $this->Prefix . 'equipment_sport') {
            $ids = $this->fetchEquipmentTypeIDs();
            $query .= ' WHERE `equipment_typeid` IN(' . implode(',', $ids) . ')';
        } elseif ($tableName === $this->Prefix . 'equipment_to_sport') {
            $ids = $this->fetchEquipmentIDs();
            $query .= ' WHERE `equipment_id` IN(' . implode(',', $ids) . ')';
        } elseif ($tableName === $this->Prefix . 'activity_equipment') {
            $ids = $this->fetchEquipmentIDs();
            $query .= ' WHERE `equipmentid` IN(' . implode(',', $ids) . ')';
        } elseif ($tableName === $this->Prefix . 'activity_tag') {
            $ids = $this->fetchTagIDs();
            $query .= ' WHERE `tagid` IN(' . implode(',', $ids) . ')';
        } else {
            $query .= ' WHERE `accountid`=' . $this->AccountID;
        }

        return $ids;
    }

    protected function startTableRows(string $tableName)
    {
    }

    protected function finishTableRows()
    {
    }

    private function fetchPluginIDs(): array
    {
        return $this->PDO->query('SELECT `id` FROM `' . $this->Prefix . 'plugin` WHERE `accountid`=' . $this->AccountID)->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function fetchEquipmentIDs(): array
    {
        return $this->PDO->query('SELECT `id` FROM `' . $this->Prefix . 'equipment` WHERE `accountid`=' . $this->AccountID)->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function fetchTagIDs(): array
    {
        return $this->PDO->query('SELECT `id` FROM `' . $this->Prefix . 'tag` WHERE `accountid`=' . $this->AccountID)->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function fetchEquipmentTypeIDs(): array
    {
        return $this->PDO->query('SELECT `id` FROM `' . $this->Prefix . 'equipment_type` WHERE `accountid`=' . $this->AccountID)->fetchAll(\PDO::FETCH_COLUMN);
    }

    abstract protected function saveRowsFromStatement(string $tableName, array $columnInfo, \PDOStatement $statement);
}
