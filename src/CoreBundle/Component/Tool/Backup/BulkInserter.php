<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

class BulkInserter
{
    protected \PDOStatement $Statement;

    protected \PDOforRunalyze $DB;

    public function __construct(string $tableName, array $columns, int $accountID, string $databasePrefix)
    {
        $this->DB = \DB::getInstance();

        $this->prepareStatement($tableName, $columns, $accountID, $databasePrefix);
    }

    protected function prepareStatement(string $tableName, array $columns, int $accountID, string $databasePrefix): void
    {
        $tableName = str_replace('runalyze_', $databasePrefix, $tableName);
        $PreparedColumns = '`' . implode('`,`', $columns) . '`';
        $PreparedValues = implode(',', array_fill(0, count($columns), '?'));

        if (
            ! in_array($tableName, [$databasePrefix . 'equipment_sport', $databasePrefix . 'equipment_to_sport', $databasePrefix . 'activity_equipment', $databasePrefix . 'activity_tag'], true)
        ) {
            $PreparedColumns .= ',`accountid`';
            $PreparedValues .= ',' . $accountID;
        }

        $this->Statement = $this->DB->prepare('INSERT INTO `' . $tableName . '` (' . $PreparedColumns . ') VALUES (' . $PreparedValues . ')');
    }

    /**
     * @return int last inserted ID
     */
    public function insert(array $values): int
    {
        $this->Statement->execute($values);

        return (int) $this->DB->lastInsertId();
    }
}
