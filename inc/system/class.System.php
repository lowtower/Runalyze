<?php

/**
 * This file contains class::System
 * @package Runalyze\System
 */

/**
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.1
 */
class System
{
    /**
     * Get domain where Runalyze is running
     */
    public static function getDomain(): string
    {
        if (! isset($_SERVER['HTTP_HOST'])) {
            return '';
        }

        return '//' . $_SERVER['HTTP_HOST'];
    }

    /**
     * @param bool $onlyToRunalyze
     */
    public static function getFullDomain($onlyToRunalyze = true): string
    {
        // TODO: correct handling of /public

        $path = self::getDomain() . substr($_SERVER['SCRIPT_NAME'], 0, strripos($_SERVER['SCRIPT_NAME'], '/')) . '/';

        if ($onlyToRunalyze) {
            return str_replace(['call/', 'inc/', 'tpl/'], ['', '', ''], $path);
        }

        return $path;
    }

    public static function getFullDomainWithProtocol(): string
    {
        return Request::getProtocol() . ':' . self::getFullDomain();
    }

    /**
     * Is this script running on localhost?
     * @return bool
     */
    public static function isAtLocalhost()
    {
        if (! isset($_SERVER['SERVER_NAME'])) {
            return false;
        }

        return $_SERVER['SERVER_NAME'] == 'localhost';
    }
}
