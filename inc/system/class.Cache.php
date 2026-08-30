<?php

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
     * Path for cache, relative to runalyze root
     * @var string
     */
    public const PATH = FRONTEND_PATH . '../var/cache/legacy';

    /**
     * Last cache clean date
     * @var int
     */
    private static $LASTCLEAN;

    /**
     * ignore some keywords
     */
    private static $ignoreKeywords = ['sport'];

    /**
     * Boolean flag: Cache enabled?
     * @var bool
     */
    public $footer_sent = true;

    public static ExtendedCacheItemPoolInterface $cache;

    public function __construct()
    {
        CacheManager::setDefaultConfig(new ConfigurationOption([
            'path' => FRONTEND_PATH . '../' . self::PATH,
        ]));

        self::$cache = CacheManager::getInstance('files');
    }

    /**
     * Get Cache
     */
    public static function set(string $keyword, $data, $time, int $nousercache = 0)
    {
        $key = ($nousercache === 0)
            ? $keyword . SessionAccountHandler::getId()
            : $keyword;

        $item = self::$cache->getItem($key);
        $item->set($data)
            ->expiresAfter($time);
        self::$cache->save($item);
    }

    /**
     * Set Cache
     */
    public static function get(string $keyword, int $nousercache = 0)
    {
        if ($nousercache === 0 && ! in_array($keyword, self::$ignoreKeywords)) {

            $key = $keyword . SessionAccountHandler::getId();
            $item = self::$cache->getItem($key);

            if (! $item->isHit()) {
                return null;
            }

            if (self::$LASTCLEAN === null) {
                $lastItem = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId());
                self::$LASTCLEAN = $lastItem->isHit() ? $lastItem->get() : 0;
            }

            return $item->get();
        }

        $item = self::$cache->getItem($keyword);
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Delete from cache
     */
    public static function delete(string $keyword, int $nousercache = 0)
    {
        if (in_array($keyword, self::$ignoreKeywords)) {
            return null;
        }

        $key = ($nousercache === 0)
            ? $keyword . SessionAccountHandler::getId()
            : $keyword;

        return self::$cache->deleteItem($key);
    }

    /**
     * Clean up all cache
     */
    public static function clean()
    {
        self::$LASTCLEAN = time();

        if (SessionAccountHandler::getId() === null) {
            self::$cache->clear();
        } else {
            $item = self::$cache->getItem('LASTCLEAN' . SessionAccountHandler::getId());
            $item->set(self::$LASTCLEAN);
            self::$cache->save($item);
        }
    }

    /**
     * is existing?
     */
    public static function is(string $keyword, int $nousercache = 0)
    {
        $key = ($nousercache === 0)
            ? $keyword . SessionAccountHandler::getId()
            : $keyword;

        return self::$cache->hasItem($key);
    }
}
