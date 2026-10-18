<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;

#[Group('default')]
class LegacyCacheTest extends TestCase
{
    private string $cacheDir;

    private LegacyCache $legacyCache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/legacy_cache_test_' . uniqid('', true);
        mkdir($this->cacheDir, 0777, true);

        $this->legacyCache = new LegacyCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->cacheDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function seedCacheItem(string $keyword, int|bool $accountId, string $value = 'cached-value'): void
    {
        $property = new \ReflectionProperty(LegacyCache::class, 'cache');
        $pool = $property->getValue($this->legacyCache);

        $accountIdPart = $accountId === false ? '' : (string) $accountId;
        $item = $pool->getItem($keyword . $accountIdPart);
        $item->set($value);

        $pool->save($item);
    }

    public function testDeleteReturnsFalseWhenKeyDoesNotExist(): void
    {
        self::assertFalse($this->legacyCache->delete('nonexistent-keyword'));
    }

    public function testDeleteReturnsTrueAndRemovesExistingItemWithoutAccountId(): void
    {
        $this->seedCacheItem('training42', false);

        self::assertTrue($this->legacyCache->delete('training42'));
        self::assertFalse($this->legacyCache->delete('training42'), 'the item must actually be gone after the first delete');
    }

    public function testDeleteReturnsTrueAndRemovesExistingItemWithAccountId(): void
    {
        $this->seedCacheItem('training42', 7);

        self::assertTrue($this->legacyCache->delete('training42', 7));
        self::assertFalse($this->legacyCache->delete('training42', 7));
    }

    public function testDeleteKeyCollisionBetweenDifferentKeywordAccountIdCombinations(): void
    {
        $this->seedCacheItem('training4', 2); // key becomes "training42"

        self::assertTrue($this->legacyCache->delete('training42', false));
    }

    public function testDeleteWithDifferentAccountIdsAreIndependentKeys(): void
    {
        $this->seedCacheItem('route5', 1);
        $this->seedCacheItem('route5', 2);

        self::assertTrue($this->legacyCache->delete('route5', 1));
        // The item scoped to account 2 must still exist independently.
        self::assertTrue($this->legacyCache->delete('route5', 2));
    }

    public function testDeleteWithoutAccountIdDoesNotAffectItemScopedToAnAccountId(): void
    {
        $this->seedCacheItem('route5', 3);

        self::assertFalse($this->legacyCache->delete('route5'));
        self::assertTrue($this->legacyCache->delete('route5', 3), 'the account-scoped item must remain untouched by the previous call');
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    private function createAccountWithId(int $id): Account
    {
        $account = new Account();
        $this->setEntityId($account, $id);

        return $account;
    }

    private function createTrainingWithId(int $id, Account $account): Training
    {
        $training = new Training();
        $training->setAccount($account);
        $this->setEntityId($training, $id);

        return $training;
    }

    private function createRouteWithId(int $id): Route
    {
        $route = new Route();
        $this->setEntityId($route, $id);

        return $route;
    }

    public function testClearActivityCacheDeletesOnlyTheTrainingKeyWhenNoRelatedEntitiesArePresent(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);

        $this->seedCacheItem('training42', 5);

        $this->legacyCache->clearActivityCache($training);

        self::assertFalse($this->legacyCache->delete('training42', 5), 'the training cache item must already be gone');
    }

    public function testClearActivityCacheAlsoDeletesRouteKeyWhenRouteIsPresent(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);
        $route = $this->createRouteWithId(99);
        $training->setRoute($route);

        $this->seedCacheItem('training42', 5);
        $this->seedCacheItem('route99', 5);

        $this->legacyCache->clearActivityCache($training);

        self::assertFalse($this->legacyCache->delete('training42', 5), 'the training cache item must already be gone');
        self::assertFalse($this->legacyCache->delete('route99', 5), 'the route cache item must already be gone');
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testClearActivityCacheDoesNotThrowWhenNothingWasEverCached(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);
        $route = $this->createRouteWithId(99);
        $training->setRoute($route);

        $this->legacyCache->clearActivityCache($training);
    }

    public function testClearActivityCacheDeletesTrackdataSwimdataHrvAndRaceresultKeysWhenAllArePresent(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);

        $trackdata = new Trackdata();
        $trackdata->setActivity($training);

        $training->setTrackdata($trackdata);

        $swimdata = new Swimdata();
        $swimdata->setActivity($training);

        $training->setSwimdata($swimdata);

        $hrv = new Hrv();
        $hrv->setActivity($training);

        $training->setHrv($hrv);

        $raceresult = new Raceresult();
        $raceresult->setActivity($training);

        $training->setRaceresult($raceresult);

        $this->seedCacheItem('training42', 5);
        $this->seedCacheItem('trackdata42', 5);
        $this->seedCacheItem('swimdata42', 5);
        $this->seedCacheItem('hrv42', 5);
        $this->seedCacheItem('raceresult42', 5);

        $this->legacyCache->clearActivityCache($training);

        self::assertFalse($this->legacyCache->delete('training42', 5));
        self::assertFalse($this->legacyCache->delete('trackdata42', 5));
        self::assertFalse($this->legacyCache->delete('swimdata42', 5));
        self::assertFalse($this->legacyCache->delete('hrv42', 5));
        self::assertFalse($this->legacyCache->delete('raceresult42', 5));
    }

    public function testClearActivityCacheDoesNotDeleteTrackdataSwimdataHrvRaceresultKeysWhenAbsent(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);
        // No trackdata/swimdata/hrv/raceresult attached.

        $this->seedCacheItem('trackdata42', 5);
        $this->seedCacheItem('swimdata42', 5);
        $this->seedCacheItem('hrv42', 5);
        $this->seedCacheItem('raceresult42', 5);

        $this->legacyCache->clearActivityCache($training);

        // None of these were touched, since their instanceof guards never fired.
        self::assertTrue($this->legacyCache->delete('trackdata42', 5));
        self::assertTrue($this->legacyCache->delete('swimdata42', 5));
        self::assertTrue($this->legacyCache->delete('hrv42', 5));
        self::assertTrue($this->legacyCache->delete('raceresult42', 5));
    }

    public function testClearRaceResultCacheDeletesKeyBasedOnActivityId(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);
        $raceresult = new Raceresult();
        $raceresult->setAccount($account);
        $raceresult->setActivity($training);

        $this->seedCacheItem('raceresult42', 5);

        $this->legacyCache->clearRaceResultCache($raceresult);

        self::assertFalse($this->legacyCache->delete('raceresult42', 5));
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testClearRaceResultCacheDoesNotThrowWhenNothingWasEverCached(): void
    {
        $account = $this->createAccountWithId(5);
        $training = $this->createTrainingWithId(42, $account);
        $raceresult = new Raceresult();
        $raceresult->setAccount($account);
        $raceresult->setActivity($training);

        $this->legacyCache->clearRaceResultCache($raceresult);
    }
}
