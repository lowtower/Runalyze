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
    /**
     * Private PDO instance
     */
    private static ?\PDOforRunalyze $PDO = null;

    private function __construct()
    {
    }

    /**
     * Private clone
     */
    private function __clone()
    {
    }

    /**
     * Create connection
     */
    public static function connect(string $host, string $port, string $user, string $password, string $database): void
    {
        self::$PDO = new PDOforRunalyze('mysql:dbname=' . $database . ';host=' . $host . ';port=' . $port . ';charset=utf8', $user, $password);
        self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Returns DB instance or create initial connection
     */
    public static function getInstance(): PDOforRunalyze
    {
        if (! self::$PDO instanceof \PDOforRunalyze) {
            throw new RuntimeException('No active database connection.');
        }

        return self::$PDO;
    }
}
