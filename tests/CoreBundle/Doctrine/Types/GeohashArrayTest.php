<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray;

class GeohashArrayTest extends \PHPUnit\Framework\TestCase
{
    protected GeohashArray $Type;

    protected AbstractPlatform $PlatformMock;

    protected function setUp(): void
    {
        /** @var GeohashArray $type */
        $type = GeohashArray::getType(GeohashArray::GEOHASH_ARRAY);
        $this->Type = $type;
        $this->PlatformMock = $this->getMockForAbstractClass(AbstractPlatform::class);
    }

    public function testEmptyData(): void
    {
        $this->assertNull($this->Type->convertToPHPValue(null, $this->PlatformMock));
        $this->assertNull($this->Type->convertToPHPValue('', $this->PlatformMock));
        $this->assertNull($this->Type->convertToDatabaseValue([], $this->PlatformMock));
    }

    public function testSimpleData(): void
    {
        $longHashes = ['7zzzzzzzzzzz', 'u1xjnxhj49qr', 'u1xjnxhj49qr', 'u1xjnxhjr7wb', '7zzzzzzzzzzz', 'u1xjnxhmqqg2', 'u1xjnxhm6zkm'];
        $shortHashes = implode('|', ['7zzzzzzzzzzz', 'u1xjnxhj49qr', '', 'r7wb', '7zzzzzzzzzzz', 'u1xjnxhmqqg2', '6zkm']);

        $this->assertEquals($longHashes, $this->Type->convertToPHPValue($shortHashes, $this->PlatformMock));
        $this->assertEquals($shortHashes, $this->Type->convertToDatabaseValue($longHashes, $this->PlatformMock));
    }
}
