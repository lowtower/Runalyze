<?php

declare(strict_types=1);

/**
 * This file contains class::SessionAccountHandler
 * @package Runalyze\System
 */
/**
 * Class: SessionAccountHandler
 *
 * @author Michael Pohl
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.0
 */
class SessionAccountHandler
{
    /**
     * Array containing userrow from database
     */
    private static array $Account = [];

    /**
     * Is anyone logged in?
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['accountid']);
    }

    /**
     * Set internal account-data
     */
    public static function setAccount(array $Account = []): void
    {
        self::$Account = $Account;
    }

    /**
     * Try to set account from request
     */
    public static function setAccountFromRequest(): void
    {
        if (self::$Account === []) {
            self::$Account = DB::getInstance()->query('SELECT * FROM `' . PREFIX . 'account` WHERE `id`="' . SharedLinker::getUserId() . '" LIMIT 1')->fetch();
        }
    }

    /**
     * Get ID of current user
     */
    public static function getId(): int
    {
        if (SharedLinker::isOnSharedPage()) {
            return SharedLinker::getUserId();
        }

        return self::$Account['id'] ?? -1;
    }

    /**
     * Get name of current user
     */
    public static function getName(): string
    {
        if (! isset(self::$Account['name'])) {
            return '';
        }

        return self::$Account['name'];
    }

    /**
     * Get users gender
     * @return mixed
     */
    public static function getGender()
    {
        if (! isset(self::$Account['gender'])) {
            return null;
        }

        return self::$Account['gender'];
    }

    /**
     * Get users year of birth
     */
    public static function getBirthYear(): ?int
    {
        if (! isset(self::$Account['birthyear'])) {
            return null;
        }

        return self::$Account['birthyear'];
    }

    /**
     * Get language of current user
     */
    public static function getLanguage(): string
    {
        if (! isset(self::$Account['language'])) {
            return '';
        }

        return self::$Account['language'];
    }

    /**
     * Get timezone of current user
     */
    public static function getTimezone(): string
    {
        if (! isset(self::$Account['timezone'])) {
            return '';
        }

        return (string) self::$Account['timezone'];
    }

    /**
     * Get username of current user
     */
    public static function getUsername(): string
    {
        if (! isset(self::$Account['username'])) {
            return '';
        }

        return self::$Account['username'];
    }
}
