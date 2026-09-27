<?php

declare(strict_types=1);

/**
 * This file contains class::DB
 * @package Runalyze\System
 */
/**
 * Database
 *
 * Database class using PDO
 *
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.0
 */
class DB
{
    private static ?\PDOforRunalyze $PDO = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Create connection
     *
     * A connection, once established, is kept for the remainder of the
     * process. Repeated calls (e.g. tests/bootstrap.php followed by any
     * Frontend instantiation within the same PHPUnit process) must not
     * silently replace an already established connection, since PDO is
     * held in a static property shared across the whole process.
     */
    public static function connect(string $host, string $port, string $user, string $password, string $database): void
    {
        if (self::$PDO instanceof \PDOforRunalyze) {
            return;
        }

        self::$PDO = new PDOforRunalyze('mysql:dbname=' . $database . ';host=' . $host . ';port=' . $port . ';charset=utf8', $user, $password);
        self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance(): PDOforRunalyze
    {
        if (! self::$PDO instanceof \PDOforRunalyze) {
            throw new RuntimeException('No active database connection.');
        }

        return self::$PDO;
    }
}
