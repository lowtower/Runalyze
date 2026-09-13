<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

use PDOforRunalyze;
use Runalyze\Configuration;
use Runalyze\Util\File\GZipReader;

class JsonImporter
{
    /**
     * @var \Runalyze\Util\File\GZipReader
     */
    protected $Reader;

    /**
     * @var array<string, array<int, int>> Maps [table][oldID] = newID
     */
    protected $ReplaceIDs = [];

    protected array $ExistingData = [];

    protected array $InternalSportIds = [];

    protected JsonImporterResults $Results;

    protected bool $OverwriteConfig = false;

    protected bool $OverwriteDataset = false;

    protected bool $OverwritePlugins = false;

    /**
     * @param string $fileName absolute path
     */
    public function __construct(
        string $fileName,
        protected PDOforRunalyze $DB,
        protected int $AccountID,
        protected string $DatabasePrefix
    ) {
        $this->Reader = new GZipReader($fileName);
        $this->Results = new JsonImporterResults();
    }

    public function resultsAsString(): string
    {
        return $this->Results->completeString();
    }

    public function enableOverwritingConfig(bool $flag = true): void
    {
        $this->OverwriteConfig = $flag;
    }

    public function enableOverwritingDataset(bool $flag = true): void
    {
        $this->OverwriteDataset = $flag;
    }

    public function enableOverwritingPlugins(bool $flag = true): void
    {
        $this->OverwritePlugins = $flag;
    }

    public function deleteOldActivities(): void
    {
        $this->truncateTable('training');
        $this->truncateTable('route');
    }

    public function deleteOldBodyValues(): void
    {
        $this->truncateTable('user');
    }

    public function importData(): void
    {
        $this->readExistingData();
        $this->readFile();
        $this->correctConfigReferences();

        \Cache::clean();
    }

    /**
     * @param string $table without prefix
     */
    private function truncateTable(string $table): void
    {
        $this->Results->addDeletes('runalyze_' . $table, $this->DB->query('DELETE FROM `' . $this->DatabasePrefix . $table . '` WHERE `accountid`="' . $this->AccountID . '"')->rowCount());
    }

    private function readExistingData(): void
    {
        Configuration::loadAll();

        $Tables = [
            'sport' => 'name',
            'type' => 'name',
            'plugin' => 'key',
            'equipment' => 'name',
            'equipment_type' => 'name',
            'tag' => 'tag',
        ];

        foreach ($Tables as $Table => $Column) {
            $this->ExistingData['runalyze_' . $Table] = [];
            if ($Table === 'sport') {
                $statement = $this->DB->query('SELECT `id`, `internal_sport_id`, `' . $Column . '` FROM `' . $this->DatabasePrefix . $Table . '` WHERE `accountid`=' . $this->AccountID);
            } else {
                $statement = $this->DB->query('SELECT `id`,`' . $Column . '` FROM `' . $this->DatabasePrefix . $Table . '` WHERE `accountid`=' . $this->AccountID);
            }

            while ($row = $statement->fetch()) {
                $this->ExistingData['runalyze_' . $Table][$row[$Column]] = $row['id'];
                if ($Table === 'sport' && ! empty($row['internal_sport_id'])) {
                    $this->InternalSportIds[$row['internal_sport_id']] = $row['id'];
                }
            }
        }

        $FetchEquipmentSportRelation = $this->DB->query('SELECT CONCAT(`sportid`, "-", `equipment_typeid`) FROM `' . $this->DatabasePrefix . 'equipment_sport`');
        $this->ExistingData['runalyze_equipment_sport'] = [];

        while ($Relation = $FetchEquipmentSportRelation->fetchColumn()) {
            $this->ExistingData['runalyze_equipment_sport'][] = $Relation;
        }

        $FetchEquipmentSporRelation = $this->DB->query('SELECT CONCAT(`sportid`, "-", `equipment_id`) FROM `' . $this->DatabasePrefix . 'equipment_to_sport`');
        $this->ExistingData['runalyze_equipment_to_sport'] = [];

        while ($Relation = $FetchEquipmentSporRelation->fetchColumn()) {
            $this->ExistingData['runalyze_equipment_to_sport'][] = $Relation;
        }
    }

    private function readFile(): void
    {
        while (! $this->Reader->eof()) {
            $line = trim($this->Reader->readLine());

            if (str_starts_with($line, '{"TABLE"')) {
                $tableName = substr($line, 10, -2);
                $this->readTable($tableName);
            }
        }
    }

    private function readTable(string $tableName): void
    {
        $tableSettings = [
            'import' => [
                'runalyze_sport',
                'runalyze_type',
                'runalyze_user',
                'runalyze_equipment_type',
                'runalyze_equipment_sport',
                'runalyze_equipment_to_sport',
                'runalyze_equipment',
                'runalyze_route',
                'runalyze_training',
                'runalyze_swimdata',
                'runalyze_trackdata',
                'runalyze_hrv',
                'runalyze_activity_equipment',
                'runalyze_tag',
                'runalyze_activity_tag',
                'runalyze_raceresult',
            ],
            'update' => [
                'runalyze_conf' => $this->OverwriteConfig,
                'runalyze_dataset' => $this->OverwriteDataset,
                'runalyze_plugin' => $this->OverwritePlugins,
                'runalyze_plugin_conf' => $this->OverwritePlugins,
            ],
        ];

        if (in_array($tableName, $tableSettings['import'], true)) {
            $this->importTable($tableName);
        } elseif (isset($tableSettings['update'][$tableName]) && $tableSettings['update'][$tableName]) {
            $this->updateTable($tableName);
        }
    }

    private function updateTable(string $tableName): void
    {
        $line = $this->Reader->readLine();

        if (! is_string($line) || $line === '' || $line[0] !== '{') {
            return;
        }

        $startedTransaction = ! $this->DB->inTransaction();
        if ($startedTransaction) {
            $this->DB->beginTransaction();
        }

        $statement = $this->prepareUpdateStatement($tableName);

        while (is_string($line) && $line !== '' && $line[0] === '{') {
            $completeRow = json_decode($line, true);
            $id = (int) key($completeRow);
            $row = current($completeRow);

            $this->runPreparedStatement($tableName, $statement, $id, $row);

            $line = $this->Reader->readLine();
        }

        if ($startedTransaction) {
            $this->DB->commit();
        }
    }

    /**
     * @param 'runalyze_conf'|'runalyze_dataset'|'runalyze_plugin'|'runalyze_plugin_conf' $tableName
     */
    private function prepareUpdateStatement(string $tableName): \PDOStatement
    {
        return match ($tableName) {
            'runalyze_conf' => $this->DB->prepare('UPDATE `' . $this->DatabasePrefix . 'conf` SET `value`=? WHERE `accountid`=' . $this->AccountID . ' AND `key`=?'),
            'runalyze_dataset' => $this->DB->prepare('
						UPDATE `' . $this->DatabasePrefix . 'dataset`
						SET
							`active`=?,
							`style`=?,
							`position`=?
						WHERE `accountid`=' . $this->AccountID . ' AND `keyid`=?'),
            'runalyze_plugin' => $this->DB->prepare('UPDATE `' . $this->DatabasePrefix . 'plugin` SET `active`=?, `order`=? WHERE `accountid`=' . $this->AccountID . ' AND `key`=?'),
            'runalyze_plugin_conf' => $this->DB->prepare('UPDATE `' . $this->DatabasePrefix . 'plugin_conf` SET `value`=? WHERE `pluginid`=? AND `config`=?'),
            default => throw new \LogicException('Unhandled table name'),
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function runPreparedStatement(string $tableName, \PDOStatement $statement, int $id, array $row): void
    {
        switch ($tableName) {
            case 'runalyze_conf':
                $statement->execute([$row['value'], $row['key']]);
                break;

            case 'runalyze_dataset':
                $statement->execute([
                    $row['active'],
                    $row['style'],
                    $row['position'],
                    $row['keyid'],
                ]);
                break;

            case 'runalyze_plugin':
                if (isset($this->ExistingData['runalyze_plugin'][$row['key']])) {
                    $this->ExistingData['runalyze_plugin'][$id] = $this->ExistingData['runalyze_plugin'][$row['key']];
                }

                $statement->execute([$row['active'], $row['order'], $row['key']]);
                break;

            case 'runalyze_plugin_conf':
                $pluginId = $this->ExistingData['runalyze_plugin'][$row['pluginid']] ?? null;
                if ($pluginId === null) {
                    break;
                }

                $statement->execute([$row['value'], $pluginId, $row['config']]);
                break;

            default:
                return;
        }

        $this->Results->addUpdates($tableName, $statement->rowCount());
    }

    private function importTable(string $tableName): void
    {
        $line = $this->Reader->readLine();

        if (! is_string($line) || $line === '' || $line[0] !== '{') {
            return;
        }

        $completeRow = json_decode($line, true);
        $row = array_shift($completeRow);
        $columns = array_keys($row);

        $bulkInserter = new BulkInserter($tableName, $columns, $this->AccountID, $this->DatabasePrefix);

        while (is_string($line) && $line !== '' && $line[0] === '{') {
            $completeRow = json_decode($line, true);
            $id = key($completeRow);
            $row = current($completeRow);
            $values = array_values($row);
            $columnIds = array_flip($columns);
            if (in_array(
                $tableName,
                [
                    'runalyze_equipment',
                    'runalyze_equipment_type',
                    'runalyze_plugin',
                    'runalyze_route',
                    'runalyze_type',
                    'runalyze_sport',
                    'runalyze_tag',
                ],
                true
            )) {
                if ($tableName === 'runalyze_sport' && isset($columnIds['internal_sport_id'])) {
                    $internalId = $values[$columnIds['internal_sport_id']];

                    if (isset($this->InternalSportIds[$internalId])) {
                        $this->ReplaceIDs[$tableName][$id] = $this->InternalSportIds[$internalId];
                        $line = $this->Reader->readLine();

                        continue;
                    }
                }

                if (isset($this->ExistingData[$tableName][$values[0]])) {
                    $this->ReplaceIDs[$tableName][$id] = $this->ExistingData[$tableName][$values[0]];
                } else {
                    $this->correctValues($tableName, $row);

                    $this->ReplaceIDs[$tableName][$id] = $bulkInserter->insert(array_values($row));
                    $this->Results->addInserts($tableName, 1);
                }
            } elseif (
                $tableName === 'runalyze_equipment_sport' &&
                $this->equipmentSportRelationDoesExist($row['sportid'], $row['equipment_typeid'])
            ) {
                // Hint: Don't insert this relation, it does exist already!
            } elseif (
                $tableName === 'runalyze_equipment_to_sport' &&
                $this->equipmentSporRelationDoesExist($row['sportid'], $row['equipment_id'])
            ) {
                // Hint: Don't insert this relation, it does exist already!
            } else {
                $this->correctValues($tableName, $row);

                if ($tableName === 'runalyze_training') {
                    $this->ReplaceIDs[$tableName][$id] = $bulkInserter->insert(array_values($row));
                } else {
                    $bulkInserter->insert(array_values($row));
                }

                $this->Results->addInserts($tableName, 1);
            }

            $line = $this->Reader->readLine();
        }

    }

    protected function equipmentSportRelationDoesExist(int $sportid, int $equipmentTypeid): bool
    {
        return isset($this->ReplaceIDs['runalyze_sport'][$sportid]) &&
            isset($this->ReplaceIDs['runalyze_equipment_type'][$equipmentTypeid]) &&
            in_array(
                $this->ReplaceIDs['runalyze_sport'][$sportid] . '-' . $this->ReplaceIDs['runalyze_equipment_type'][$equipmentTypeid],
                $this->ExistingData['runalyze_equipment_sport']
            );
    }

    protected function equipmentSporRelationDoesExist(int $sportid, int $equipmentId): bool
    {
        return isset($this->ReplaceIDs['runalyze_sport'][$sportid]) &&
            isset($this->ReplaceIDs['runalyze_equipment'][$equipmentId]) &&
            in_array(
                $this->ReplaceIDs['runalyze_sport'][$sportid] . '-' . $this->ReplaceIDs['runalyze_equipment'][$equipmentId],
                $this->ExistingData['runalyze_equipment_to_sport']
            );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function correctValues(string $tableName, array &$row): void
    {
        if ($tableName === 'runalyze_training') {
            $this->correctActivity($row);
        } elseif ($tableName === 'runalyze_sport') {
            if (isset($row['default_typeid'])) {
                $row['default_typeid'] = null;
            }

            if (isset($row['main_equipmenttypeid'])) {
                $row['main_equipmenttypeid'] = null;
            }
        } elseif ($tableName === 'runalyze_type') {
            $row['sportid'] = $this->correctID('runalyze_sport', $row['sportid']);
        } elseif ($tableName === 'runalyze_plugin_conf') {
            $row['pluginid'] = $this->correctID('runalyze_plugin', $row['pluginid']);
        } elseif ($tableName === 'runalyze_trackdata') {
            $row['activityid'] = $this->correctID('runalyze_training', $row['activityid']);
        } elseif ($tableName === 'runalyze_swimdata') {
            $row['activityid'] = $this->correctID('runalyze_training', $row['activityid']);
        } elseif ($tableName === 'runalyze_hrv') {
            $row['activityid'] = $this->correctID('runalyze_training', $row['activityid']);
        } elseif ($tableName === 'runalyze_equipment') {
            $row['typeid'] = $this->correctID('runalyze_equipment_type', $row['typeid']);
        } elseif ($tableName === 'runalyze_equipment_sport') {
            $row['sportid'] = $this->correctID('runalyze_sport', $row['sportid']);
            $row['equipment_typeid'] = $this->correctID('runalyze_equipment_type', $row['equipment_typeid']);
        } elseif ($tableName === 'runalyze_equipment_to_sport') {
            $row['sportid'] = $this->correctID('runalyze_sport', $row['sportid']);
            $row['equipment_id'] = $this->correctID('runalyze_equipment', $row['equipment_id']);
        } elseif ($tableName === 'runalyze_activity_equipment') {
            $row['activityid'] = $this->correctID('runalyze_training', $row['activityid']);
            $row['equipmentid'] = $this->correctID('runalyze_equipment', $row['equipmentid']);
        } elseif ($tableName === 'runalyze_activity_tag') {
            $row['activityid'] = $this->correctID('runalyze_training', $row['activityid']);
            $row['tagid'] = $this->correctID('runalyze_tag', $row['tagid']);
        } elseif ($tableName === 'runalyze_raceresult') {
            $row['activity_id'] = $this->correctID('runalyze_training', $row['activity_id']);
        }
    }

    /**
     * @param array<string, mixed> $activity
     */
    private function correctActivity(array &$activity): void
    {
        if (isset($activity['sportid'])) {
            $activity['sportid'] = $this->correctID('runalyze_sport', $activity['sportid']);
        }

        if (isset($activity['typeid'])) {
            $activity['typeid'] = $this->correctID('runalyze_type', $activity['typeid']);
        }

        if (isset($activity['routeid'])) {
            $activity['routeid'] = $this->correctID('runalyze_route', $activity['routeid']);
        }
    }

    /**
     * @return int
     */
    private function correctID(string $table, int $id)
    {
        return $this->ReplaceIDs[$table][$id] ?? 0;
    }

    /**
     * Correct references in configuration
     */
    private function correctConfigReferences(): void
    {
        if (isset($_POST['overwrite_config'])) {
            $configValues = Configuration\Handle::tableHandles();

            foreach ($configValues as $key => $table) {
                $table = 'runalyze_' . $table;

                if (isset($this->ReplaceIDs[$table])) {
                    $oldValue = $this->DB->query('SELECT `value` FROM `' . $this->DatabasePrefix . 'conf` WHERE `key`="' . $key . '" LIMIT 1')->fetchColumn();
                    $newValue = $this->correctID($table, $oldValue);

                    if ($newValue != 0) {
                        $this->DB->updateWhere('conf', '`key`="' . $key . '"', 'value', $newValue);
                    }
                }
            }
        }
    }
}
