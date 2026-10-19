<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\System;

use Cache;
use DateInterval;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class CacheTest extends TestCase
{
    public function testCachePathDoesNotRepeatTheFrontendPathSegment(): void
    {
        $frontendPathWithoutTrailingSlash = rtrim(FRONTEND_PATH, '/');

        $this->assertSame(
            1,
            substr_count(Cache::PATH, $frontendPathWithoutTrailingSlash),
            'The configured cache path must not contain the FRONTEND_PATH segment more than once.'
        );
    }

    public function testConstructingCacheRepeatedlyInTheSameProcessDoesNotEmitANotice(): void
    {
        new Cache();
        new Cache();

        $this->assertDirectoryExists(Cache::PATH);
        $this->assertDirectoryIsWritable(Cache::PATH);
    }

    public function testGetReturnsNullForKeywordThatWasNeverSet(): void
    {
        $this->assertNull(Cache::get(uniqid('cache_test_missing_', true)));
    }

    public function testSetAndGetRoundTripForUserScopedKeyword(): void
    {
        $keyword = uniqid('cache_test_scoped_', true);

        Cache::set($keyword, 'value-1', 3600);

        $this->assertSame('value-1', Cache::get($keyword));

        Cache::delete($keyword);
    }

    public function testSetAcceptsDateIntervalAsExpirationTime(): void
    {
        $keyword = uniqid('cache_test_interval_', true);

        Cache::set($keyword, 'value-2', new DateInterval('PT1H'));

        $this->assertSame('value-2', Cache::get($keyword));

        Cache::delete($keyword);
    }

    public function testIsReturnsFalseBeforeAndTrueAfterSettingAKeyword(): void
    {
        $keyword = uniqid('cache_test_is_', true);

        $this->assertFalse(Cache::is($keyword));

        Cache::set($keyword, 'value-3', 3600);

        $this->assertTrue(Cache::is($keyword));

        Cache::delete($keyword);
    }

    public function testDeleteRemovesAPreviouslySetKeyword(): void
    {
        $keyword = uniqid('cache_test_delete_', true);

        Cache::set($keyword, 'value-4', 3600);

        $this->assertTrue(Cache::delete($keyword));
        $this->assertFalse(Cache::is($keyword));
        $this->assertNull(Cache::get($keyword));
    }

    public function testNousercacheFlagStoresKeywordWithoutAccountSuffix(): void
    {
        $keyword = uniqid('cache_test_nouser_', true);

        Cache::set($keyword, 'shared-value', 3600, 1);

        $this->assertSame('shared-value', Cache::get($keyword, 1));
        $this->assertNull(Cache::get($keyword));

        Cache::delete($keyword, 1);
    }

    public function testSetGetAndDeleteAreConsistentForIgnoredKeywordsWithDefaultNousercacheFlag(): void
    {
        Cache::set('sport', 'marathon', 3600);

        $this->assertTrue(Cache::is('sport'));
        $this->assertSame('marathon', Cache::get('sport'));
        $this->assertTrue(Cache::delete('sport'));
        $this->assertFalse(Cache::is('sport'));
        $this->assertNull(Cache::get('sport'));
    }

    public function testIgnoredKeywordIsStoredUnscopedRegardlessOfTheNousercacheFlag(): void
    {
        Cache::set('sport', 'marathon', 3600, 1);

        $this->assertSame(
            'marathon',
            Cache::get('sport'),
            'Ignored keywords must always be stored unscoped, whether nousercache is passed as 0 or 1.'
        );

        Cache::delete('sport');
    }

    public function testGetPopulatesLastCleanTimestampOnFirstCacheHitInTheProcess(): void
    {
        $keyword = uniqid('cache_test_lastclean_', true);
        Cache::set($keyword, 'value-5', 3600);

        $lastCleanProperty = new ReflectionProperty(Cache::class, 'LASTCLEAN');
        $lastCleanProperty->setValue(null, 0);

        Cache::get($keyword);

        $this->assertIsInt($lastCleanProperty->getValue());

        Cache::delete($keyword);
    }
}
