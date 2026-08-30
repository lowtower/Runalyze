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

    public function startAddingAccountID()
    {
        $this->addsAccountID = true;
    }

    public function stopAddingAccountID()
    {
        $this->addsAccountID = false;
    }

    public function setAccountID(int $ID)
    {
        $this->accountID = $ID;
    }

    protected function addAccountIDtoStatement(string &$statement)
    {
        if (! is_numeric($this->accountID)
            || strpos($statement, 'SET NAMES') !== false
            || strpos($statement, 'TRUNCATE') !== false
            || strpos($statement, 'accountid') !== false
            || strpos($statement, 'FROM') === false
        ) {
            return;
        }

        if (
            strpos($statement, PREFIX . 'account') === false
            && strpos($statement, PREFIX . 'plugin_conf') === false
            && strpos($statement, PREFIX . 'activity_equipment') === false
            && strpos($statement, PREFIX . 'activity_tag') === false
            && strpos($statement, PREFIX . 'equipment_sport') === false
            && strpos($statement, PREFIX . 'equipment_spor') === false
            && strpos($statement, PREFIX . 'weathercache') === false
            && strpos($statement, PREFIX . 'raceresult') === false
            && strpos($statement, '`accountid`') === false
            && strpos($statement, 'accountid=') === false
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
     * @return array
     */
    public function fetchByID(string $table, int $ID)
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
    public function updateWhere(string $table, string $where, $column, $value)
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
     * @param string   $table without PREFIX
     * @param array $columns
     * @param array  $values
     * @return int       ID of inserted row
     */
    public function insert(string $table, $columns, $values): int
    {
        $table = str_replace(PREFIX, '', $table);

        // TODO: TEST IT!
        if (! in_array($table, ['account', 'plugin_conf', 'equipment_sport', 'equipment_spor'], true) && ! in_array('accountid', $columns)) {
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

        return $this->lastInsertId();
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

                if ($quotes === false && substr($values, 0, 1) == "'" && substr($values, -1) == "'" && strlen($values) > 2) {
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
        if ($this->addsAccountID) {
            $this->addAccountIDtoStatement($statement);
        }

        return parent::exec($statement);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     * @see http://php.net/manual/en/pdo.query.php
     * @param string $statement <p>The SQL statement to prepare and execute.</p>
     * @return PDOStatement <b>PDO::query</b> returns a PDOStatement object, or <b>FALSE</b>
     * on failure.
     */
    public function query(string $statement)
    {
        if ($this->addsAccountID) {
            $this->addAccountIDtoStatement($statement);
        }

        return parent::query($statement);
    }
}
