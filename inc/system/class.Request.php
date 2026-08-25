<?php

/**
 * This file contains class::Request
 * @package Runalyze\System
 */
/**
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.1
 */
class Request
{
    /**
     * Get requested URI
     * @return string
     */
    public static function Uri()
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * Is the user on a shared page?
     */
    public static function isOnSharedPage(): bool
    {
        return SharedLinker::isOnSharedPage();
    }

    /**
     * Was the request an AJAX-request?
     * Be careful: Does not work if a file is sent via jQuery!
     */
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Is request HTTPS?
     */
    public static function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }

    /**
     * Get protocol (http/https)
     */
    public static function getProtocol(): string
    {
        if (self::isHttps()) {
            return 'https';
        }

        return 'http';
    }

    /**
     * Get ID send as post or get
     * @return mixed
     */
    public static function sendId()
    {
        return $_GET['id'] ?? $_POST['id'] ?? false;
    }

    /**
     * Get parameter send via GET or POST
     * @param string $key
     * @return string
     */
    public static function param($key)
    {
        return $_GET[$key] ?? $_POST[$key] ?? '';
    }
}
