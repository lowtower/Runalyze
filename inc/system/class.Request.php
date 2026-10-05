<?php

/**
 * This file contains class::Request
 * @package Runalyze\System
 */

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.1
 */
class Request
{
    private static ?RequestStack $RequestStack = null;

    public static function setRequestStack(RequestStack $requestStack): void
    {
        self::$RequestStack = $requestStack;
    }

    private static function current(): ?SymfonyRequest
    {
        return self::$RequestStack?->getCurrentRequest();
    }

    public static function Uri(): string
    {
        $request = self::current();

        if ($request instanceof SymfonyRequest) {
            return $request->getRequestUri();
        }

        return $_SERVER['REQUEST_URI'] ?? '';
    }

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
        $request = self::current();

        if ($request instanceof SymfonyRequest) {
            return $request->isXmlHttpRequest();
        }

        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function isHttps(): bool
    {
        $request = self::current();

        if ($request instanceof SymfonyRequest) {
            return $request->isSecure();
        }

        return isset($_SERVER['HTTPS']) && ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }

    public static function getProtocol(): string
    {
        if (self::isHttps()) {
            return 'https';
        }

        return 'http';
    }

    /**
     * Get ID send as post or get
     */
    public static function sendId(): mixed
    {
        $request = self::current();

        if ($request instanceof SymfonyRequest) {
            return $request->query->get('id') ?? $request->request->get('id') ?? false;
        }

        return $_GET['id'] ?? $_POST['id'] ?? false;
    }

    public static function param(string $key): string
    {
        $request = self::current();

        if ($request instanceof SymfonyRequest) {
            return $request->query->get($key) ?? $request->request->get($key) ?? '';
        }

        return $_GET[$key] ?? $_POST[$key] ?? '';
    }
}
