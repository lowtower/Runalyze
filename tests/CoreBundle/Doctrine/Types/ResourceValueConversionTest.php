<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\GeohashArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ResourceValueConversionTest extends TestCase
{
    private AbstractPlatform&Stub $platform;

    protected function setUp(): void
    {
        $this->platform = $this->createStub(AbstractPlatform::class);
    }

    /**
     * @return resource
     */
    private function createStream(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);

        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * @param array<int, mixed> $expected
     */
    #[DataProvider('storedValues')]
    public function testResourceIsConvertedLikeTheSameText(string $typeName, string $storedValue, array $expected): void
    {
        $type = Type::getType($typeName);

        $this->assertEquals($expected, $type->convertToPHPValue($this->createStream($storedValue), $this->platform));
        $this->assertEquals($expected, $type->convertToPHPValue($storedValue, $this->platform));
    }

    public static function storedValues(): iterable
    {
        yield 'geohashes' => [
            GeohashArray::GEOHASH_ARRAY,
            '7zzzzzzzzzzz|u1xjnxhj49qr||r7wb|7zzzzzzzzzzz|u1xjnxhmqqg2|6zkm',
            ['7zzzzzzzzzzz', 'u1xjnxhj49qr', 'u1xjnxhj49qr', 'u1xjnxhjr7wb', '7zzzzzzzzzzz', 'u1xjnxhmqqg2', 'u1xjnxhm6zkm'],
        ];
        yield 'numbers' => [PipeDelimitedArray::PIPE_ARRAY, '3.14|42|0', [3.14, 42, 0]];
        yield 'strings' => [PipeDelimitedArrayString::PIPE_ARRAY, 'a||b', ['a', null, 'b']];
    }

    #[DataProvider('typeNames')]
    public function testEmptyResourceIsConvertedToNull(string $typeName): void
    {
        $type = Type::getType($typeName);

        $this->assertNull($type->convertToPHPValue($this->createStream(''), $this->platform));
    }

    public static function typeNames(): iterable
    {
        yield 'geohashes' => [GeohashArray::GEOHASH_ARRAY];
        yield 'numbers' => [PipeDelimitedArray::PIPE_ARRAY];
        yield 'strings' => [PipeDelimitedArrayString::PIPE_ARRAY];
    }
}
