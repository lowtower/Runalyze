<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class LegacyCache
{
    /**
     * @var array<string, ExtendedCacheItemPoolInterface>
     */
    private static array $sharedPools = [];

    private readonly ExtendedCacheItemPoolInterface $cache;

    public function __construct(string $path)
    {
        if (! isset(self::$sharedPools[$path])) {
            CacheManager::setDefaultConfig(
                new ConfigurationOption([
                    'path' => $path,
                ])
            );

            self::$sharedPools[$path] = CacheManager::getInstance('files');
        }

        $this->cache = self::$sharedPools[$path];
    }

    public function delete(string $keyword, int|bool $accountId = false): bool
    {
        $accountId = $accountId === false ? '' : (string) $accountId;
        $key = $keyword . $accountId;

        if (! $this->cache->hasItem($key)) {
            return false;
        }

        return $this->cache->deleteItem($key);
    }

    public function clearActivityCache(Training $activity): void
    {
        $accountId = $activity->getAccount()
            ->getId();

        if ($activity->getRoute() instanceof Route) {
            $this->delete('route' . $activity->getRoute()->getId(), $accountId);
        }

        if ($activity->getTrackdata() instanceof Trackdata) {
            $this->delete('trackdata' . $activity->getId(), $accountId);
        }

        if ($activity->getSwimdata() instanceof Swimdata) {
            $this->delete('swimdata' . $activity->getId(), $accountId);
        }

        if ($activity->getHrv() instanceof Hrv) {
            $this->delete('hrv' . $activity->getId(), $accountId);
        }

        if ($activity->getRaceresult() instanceof Raceresult) {
            $this->delete('raceresult' . $activity->getId(), $accountId);
        }

        $this->delete('training' . $activity->getId(), $accountId);
    }

    public function clearRaceResultCache(Raceresult $raceResult): void
    {
        $accountId = $raceResult->getAccount()
            ->getId();

        $this->delete('raceresult' . $raceResult->getActivity()->getId(), $accountId);
    }
}
