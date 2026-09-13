<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class LegacyCache
{
    private ExtendedCacheItemPoolInterface $cache;

    public function __construct(string $path)
    {
        CacheManager::setDefaultConfig(
            new ConfigurationOption([
                'path' => $path,
            ])
        );

        $this->cache = CacheManager::getInstance('files');
    }

    /**
     * @param int|bool $accountId
     */
    public function delete(string $keyword, $accountId = false): bool
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

        if ($activity->getRoute() !== null) {
            $this->delete('route' . $activity->getRoute()->getId(), $accountId);
        }

        if ($activity->getTrackdata() !== null) {
            $this->delete('trackdata' . $activity->getId(), $accountId);
        }

        if ($activity->getSwimdata() !== null) {
            $this->delete('swimdata' . $activity->getId(), $accountId);
        }

        if ($activity->getHrv() !== null) {
            $this->delete('hrv' . $activity->getId(), $accountId);
        }

        if ($activity->getRaceresult() !== null) {
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
