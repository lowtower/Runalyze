<?php

/**
 * This file contains class::PDOforRunalyze
 * @package Runalyze\System
 */
/**
 * Extended PDO
 *
 * This extended version of the standard PDO class adds 'accountid' when needed
 *
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.0
 */
class PDOforRunalyze extends PDO
{
    /**
     * Boolean flag: add accountid
     */
    protected bool $addsAccountID = true;

    /**
     * Accountid
     * @var mixed
     */
    protected $accountID = false;

    public function startAddingAccountID(): void
    {
        $this->addsAccountID = true;
    }

    public function stopAddingAccountID(): void
    {
        $this->addsAccountID = false;
    }

    public function setAccountID(int $ID): void
    {
        $this->accountID = $ID;
    }

    protected function addAccountIDtoStatement(string &$statement)
    {
        if (! is_numeric($this->accountID)
            || str_contains($statement, 'SET NAMES')
            || str_contains($statement, 'TRUNCATE')
            || str_contains($statement, 'accountid')
            || ! str_contains($statement, 'FROM')
        ) {
            return;
        }

        if (
            ! str_contains($statement, PREFIX . 'account')
            && ! str_contains($statement, PREFIX . 'plugin_conf')
            && ! str_contains($statement, PREFIX . 'activity_equipment')
            && ! str_contains($statement, PREFIX . 'activity_tag')
            && ! str_contains($statement, PREFIX . 'equipment_sport')
            && ! str_contains($statement, PREFIX . 'equipment_to_sport')
            && ! str_contains($statement, PREFIX . 'weathercache')
            && ! str_contains($statement, PREFIX . 'raceresult')
            && ! str_contains($statement, '`accountid`')
            && ! str_contains($statement, 'accountid=')
        ) {
            if (strpos($statement, 'WHERE') >= 7) {
                $statement = str_replace('WHERE', 'WHERE `accountid`=' . (int) $this->accountID . ' AND ', $statement);
            } elseif (preg_match('/\b(GROUP BY|HAVING|ORDER BY|LIMIT)\b/i', $statement, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                $statement = substr($statement, 0, $pos)
                    . 'WHERE `accountid`=' . (int) $this->accountID . ' '
                    . substr($statement, $pos);
            } else {
                $statement = $statement . ' WHERE `accountid`=' . (int) $this->accountID;
            }
        }
    }

    /**
     * Fetch row by id
     * @param string $table without PREFIX
     */
    public function fetchByID(string $table, int $ID): array
    {
        $table = str_replace(PREFIX, '', $table);

        if ($table == 'account' || $table == 'plugin_conf') {
            return $this->query('SELECT * FROM `' . PREFIX . $table . '` WHERE `id`=' . $ID . ' LIMIT 1')->fetch();
        }

        return $this->query('SELECT * FROM `' . PREFIX . $table . '` WHERE `id`=' . $ID . ' AND `accountid`="' . SessionAccountHandler::getId() . '" LIMIT 1')->fetch();
    }

    /**
     * Fetch row by id
     * @param string $table without PREFIX
     */
    public function deleteByID(string $table, int $ID): PDOStatement
    {
        $table = str_replace(PREFIX, '', $table);

        return $this->query('DELETE FROM `' . PREFIX . $table . '` WHERE `id`=' . $ID . ' LIMIT 1');
    }

    /**
     * Updates a table for a given ID.
     * @param string  $table without PREFIX
     * @param mixed   $column might be an array
     * @param mixed   $value  might be an array
     */
    public function update(string $table, int $id, $column, $value): void
    {
        $this->updateWhere($table, '`id`="' . $id . '" LIMIT 1', $column, $value);
    }

    /**
     * Update all rows for a given where-clause
     * @param string  $table without PREFIX
     * @param mixed   $column might be an array
     * @param mixed   $value  might be an array
     */
    public function updateWhere(string $table, string $where, $column, $value): void
    {
        $table = str_replace(PREFIX, '', $table);

        if (is_array($column) && count($column) === count($value)) {
            $set = '';
            foreach ($column as $i => $col) {
                $set .= '`' . $col . '`=' . self::escape($value[$i], true, ($col == 'clothes')) . ', ';
            }
        } else {
            $set = '`' . $column . '`=' . self::escape($value, true, ($column == 'clothes')) . ', ';
        }

        $this->query('UPDATE `' . PREFIX . $table . '` SET ' . substr($set, 0, -2) . ' WHERE ' . $where);
    }

    /**
     * Escapes and inserts the given $values to the $columns in $table
     *
     * This methods always adds the accountid (unless something is inserted to the account table)
     * @param string $table without PREFIX
     * @param array $columns
     * @param array $values
     * @return int|bool       ID of inserted row
     */
    public function insert(string $table, $columns, $values): bool|int
    {
        $table = str_replace(PREFIX, '', $table);

        // TODO: TEST IT!
        if (! in_array($table, ['account', 'plugin_conf', 'equipment_sport', 'equipment_to_sport'], true) && ! in_array('accountid', $columns)) {
            $columns[] = 'accountid';
            $values[] = $this->accountID;
        }

        foreach ($columns as $k => $v) {
            $columns[$k] = '`' . $v . '`';
        }

        $columns = implode(', ', $columns);
        $values = implode(', ', self::escape($values));

        $this->stopAddingAccountID();
        if (! $this->query('INSERT INTO `' . PREFIX . $table . '` (' . $columns . ') VALUES(' . $values . ')')) {
            $this->startAddingAccountID();
            return false;
        }

        $this->startAddingAccountID();

        return (int) $this->lastInsertId();
    }

    /**
     * Escapes values for safe mysql-queries:
     *    - Sets null-objects to 'null'
     *    - Sets true/false to 1/0
     *    - Sets strings to "$value"
     * @param mixed $values might be an array
     * @param bool $quotes  true for adding quotes for strings
     * @return mixed        safe value(s)
     */
    public function escape($values, bool $quotes = true, bool $forceAsString = false)
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = self::escape($value, $quotes);
            }
        } elseif (is_bool($values)) {
            $values = $values ? 1 : 0;
        } elseif (! is_numeric($values) || $forceAsString) {
            if ($values === null || $values == 'NULL') {
                $values = 'NULL';
            } elseif (! is_numeric($values) || $forceAsString) {
                $values = $this->quote($values);

                if ($quotes === false && str_starts_with($values, "'") && str_ends_with($values, "'") && strlen($values) > 2) {
                    $values = substr($values, 1, -1);
                }
            }
        }

        return $values;
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * @see http://php.net/manual/en/pdo.prepare.php
     * @param string $statement <p>This must be a valid SQL statement for the target database server.</p>
     * @param array $driver_options [optional] <p>
     * This array holds one or more key=&gt;value pairs to set
     * attribute values for the PDOStatement object that this method
     * returns. You would most commonly use this to set the
     * PDO::ATTR_CURSOR value to
     * PDO::CURSOR_SCROLL to request a scrollable cursor.
     * Some drivers have driver specific options that may be set at
     * prepare-time.
     * </p>
     * @return PDOStatement If the database server successfully prepares the statement,
     * <b>PDO::prepare</b> returns a
     * <b>PDOStatement</b> object.
     * If the database server cannot successfully prepare the statement,
     * <b>PDO::prepare</b> returns <b>FALSE</b> or emits
     * <b>PDOException</b> (depending on error handling).
     * </p>
     * <p>
     * Emulated prepared statements does not communicate with the database server
     * so <b>PDO::prepare</b> does not check the statement.
     */
    public function prepare($statement, $driver_options = [])
    {
        if ($this->addsAccountID) {
            $this->addAccountIDtoStatement($statement);
        }

        return parent::prepare($statement, $driver_options);
    }

    /**
     * Execute an SQL statement and return the number of affected rows
     * @see http://php.net/manual/en/pdo.exec.php
     * @param string $statement <p>The SQL statement to prepare and execute.</p>
     * @return int <b>PDO::exec</b> returns the number of rows that were modified
     * or deleted by the SQL statement you issued. If no rows were affected,
     * <b>PDO::exec</b> returns 0.
     */
    public function exec($statement)
    {
        $driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($this->addsAccountID) {
            $this->addAccountIDtoStatement($statement);
        }

        try {
            return parent::exec($statement);
        } catch (\PDOException $pdoException) {
            throw new \RuntimeException(
                "catched PDOException: error with SQL ({$driver}):\n{$statement}\n\n" . $pdoException->getMessage(),
                0,
                $pdoException
            );
        }
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     * @see http://php.net/manual/en/pdo.query.php
     * @param string $statement <p>The SQL statement to prepare and execute.</p>
     * @return PDOStatement <b>PDO::query</b> returns a PDOStatement object, or <b>FALSE</b>
     * on failure.
     */
    public function query(string $statement, ?int $fetchMode = null, mixed ...$fetchModeArgs)
    {
        if ($this->addsAccountID) {
            $this->addAccountIDtoStatement($statement);
        }

        return parent::query($statement, $fetchMode, ...$fetchModeArgs);
    }
}
