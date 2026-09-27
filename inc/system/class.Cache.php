<?php

declare(strict_types=1);

/**
 * Class: Cache - Wrapper for PHPFastCache
 * @author Michael Pohl
 * @package Runalyze\System
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;

class Cache
{
    /**
     * Absolute path for the cache directory
     * @var string
     */
    public const PATH = FRONTEND_PATH . '../var/cache/legacy';

    /**
     * Last cache clean date
     */
    private static int $LASTCLEAN = 0;

    /**
     * @var string[]
     */
    private static array $ignoreKeywords = ['sport'];

    /**
     * Boolean flag: Cache enabled?
     */
    public bool $footer_sent = true;

    public static ExtendedCacheItemPoolInterface $cache;

    public function __construct()
    {
        if (isset(self::$cache)) {
            return;
        }

        CacheManager::setDefaultConfig(new ConfigurationOption([
            'path' => self::PATH,
        ]));

        self::$cache = CacheManager::getInstance('files');
    }

    private static function isDefaultScope(string $keyword, int $nousercache): bool
    {
        return $nousercache === 0 && ! in_array($keyword, self::$ignoreKeywords, true);
    }

    private static function resolveKey(string $keyword, int $nousercache): string
    {
        return self::isDefaultScope($keyword, $nousercache)
            ? $keyword . SessionAccountHandler::getId()
            : $keyword;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function set(string $keyword, mixed $data, int|DateInterval|null $time, int $nousercache = 0): void
    {
        $key = self::resolveKey($keyword, $nousercache);

        $item = self::$cache->getItem($key);
        $item->set($data)
            ->expiresAfter($time);
        self::$cache->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function get(string $keyword, int $nousercache = 0): mixed
    {
        $key = self::resolveKey($keyword, $nousercache);
        $item = self::$cache->getItem($key);

        if (! $item->isHit()) {
            return null;
        }

        if (self::isDefaultScope($keyword, $nousercache) && self::$LASTCLEAN === 0) {
            $lastItem = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId());
            self::$LASTCLEAN = $lastItem->isHit() ? $lastItem->get() : 0;
        }

        return $item->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function delete(string $keyword, int $nousercache = 0): bool
    {
        $key = self::resolveKey($keyword, $nousercache);

        return self::$cache->deleteItem($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function clean(): void
    {
        self::$LASTCLEAN = time();

        if (SessionAccountHandler::getId() === 0) {
            self::$cache->clear();
        } else {
            $item = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId());
            $item->set(self::$LASTCLEAN);
            self::$cache->save($item);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function is(string $keyword, int $nousercache = 0): bool
    {
        $key = self::resolveKey($keyword, $nousercache);

        return self::$cache->hasItem($key);
    }
}
