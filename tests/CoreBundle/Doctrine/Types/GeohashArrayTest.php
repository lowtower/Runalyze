<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray;

final class GeohashArrayTest extends \PHPUnit\Framework\TestCase
{
    protected GeohashArray $Type;

    protected function setUp(): void
    {
        /** @var GeohashArray $type */
        $type = GeohashArray::getType(GeohashArray::GEOHASH_ARRAY);
        $this->Type = $type;
    }

    public function testEmptyData(): void
    {
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $this->assertNull($this->Type->convertToPHPValue(null, $PlatformMockStub));
        $this->assertNull($this->Type->convertToPHPValue('', $PlatformMockStub));
        $this->assertNull($this->Type->convertToDatabaseValue([], $PlatformMockStub));
    }

    public function testSimpleData(): void
    {
        $longHashes = ['7zzzzzzzzzzz', 'u1xjnxhj49qr', 'u1xjnxhj49qr', 'u1xjnxhjr7wb', '7zzzzzzzzzzz', 'u1xjnxhmqqg2', 'u1xjnxhm6zkm'];
        $shortHashes = implode('|', ['7zzzzzzzzzzz', 'u1xjnxhj49qr', '', 'r7wb', '7zzzzzzzzzzz', 'u1xjnxhmqqg2', '6zkm']);
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);

        $this->assertEquals($longHashes, $this->Type->convertToPHPValue($shortHashes, $PlatformMockStub));
        $this->assertSame($shortHashes, $this->Type->convertToDatabaseValue($longHashes, $PlatformMockStub));
    }
}
