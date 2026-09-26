<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Runalyze\Bundle\CoreBundle\Entity;

class LegacyCache
{
    private readonly ExtendedCacheItemPoolInterface $cache;

    public function __construct(string $path)
    {
        CacheManager::setDefaultConfig(
            new ConfigurationOption([
                'path' => $path,
            ])
        );

        $this->cache = CacheManager::getInstance('files');
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

    public function clearActivityCache(Entity\Training $activity): void
    {
        $accountId = $activity->getAccount()
            ->getId();

        if ($activity->getRoute() instanceof Entity\Route) {
            $this->delete('route' . $activity->getRoute()->getId(), $accountId);
        }

        if ($activity->getTrackdata() instanceof Entity\Trackdata) {
            $this->delete('trackdata' . $activity->getId(), $accountId);
        }

        if ($activity->getSwimdata() instanceof Entity\Swimdata) {
            $this->delete('swimdata' . $activity->getId(), $accountId);
        }

        if ($activity->getHrv() instanceof Entity\Hrv) {
            $this->delete('hrv' . $activity->getId(), $accountId);
        }

        if ($activity->getRaceresult() instanceof Entity\Raceresult) {
            $this->delete('raceresult' . $activity->getId(), $accountId);
        }

        $this->delete('training' . $activity->getId(), $accountId);
    }

    public function clearRaceResultCache(Entity\Raceresult $raceResult): void
    {
        $accountId = $raceResult->getAccount()
            ->getId();

        $this->delete('raceresult' . $raceResult->getActivity()->getId(), $accountId);
    }
}
