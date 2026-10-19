<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;

final class PipeDelimitedArrayTest extends \PHPUnit\Framework\TestCase
{
    protected PipeDelimitedArray $TypeNumber;

    protected PipeDelimitedArrayString $TypeString;

    protected function setUp(): void
    {
        /** @var PipeDelimitedArray $typeNumber */
        $typeNumber = PipeDelimitedArray::getType(PipeDelimitedArray::PIPE_ARRAY);
        $this->TypeNumber = $typeNumber;
        /** @var PipeDelimitedArrayString $typeString */
        $typeString = PipeDelimitedArrayString::getType(PipeDelimitedArrayString::PIPE_ARRAY);
        $this->TypeString = $typeString;
    }

    public function testEmptyData(): void
    {
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $this->assertNull($this->TypeNumber->convertToPHPValue(null, $PlatformMockStub));
        $this->assertNull($this->TypeNumber->convertToPHPValue('', $PlatformMockStub));
        $this->assertNull($this->TypeNumber->convertToDatabaseValue([], $PlatformMockStub));

        $this->assertNull($this->TypeString->convertToPHPValue(null, $PlatformMockStub));
        $this->assertNull($this->TypeString->convertToPHPValue('', $PlatformMockStub));
        $this->assertNull($this->TypeString->convertToDatabaseValue([], $PlatformMockStub));
    }

    public function testSimpleData(): void
    {
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        // numeric
        $r = $this->TypeNumber->convertToPHPValue('3.14|42|0', $PlatformMockStub);
        $this->assertEquals([3.14, 42, 0], $r);
        $this->assertIsNotString($r[0]);
        $this->assertIsNumeric($r[1]);
        $this->assertIsNotString($r[2]);

        $this->assertSame('3.14|42|0', $this->TypeNumber->convertToDatabaseValue([3.14, 42, 0], $PlatformMockStub));

        // string
        $this->assertEquals(['a', 'b', 3.4], $this->TypeString->convertToPHPValue('a|b|3.4', $PlatformMockStub));
        $this->assertSame('a|b|3.4', $this->TypeString->convertToDatabaseValue(['a', 'b', 3.4], $PlatformMockStub));

        // | will be change to blank
        $this->assertSame('a|b c', $this->TypeString->convertToDatabaseValue(['a', 'b|c'], $PlatformMockStub));
    }

    public function testPartiallyEmptyData(): void
    {
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $this->assertSame('||-12.3|-11.4|-9.8', $this->TypeNumber->convertToDatabaseValue([null, null, -12.3, -11.4, -9.8], $PlatformMockStub));

        $result = $this->TypeNumber->convertToPHPValue('||-12.3|-11.4|-9.8', $PlatformMockStub);
        $this->assertEquals([null, null, -12.3, -11.4, -9.8], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);

        $this->assertSame('||a|b', $this->TypeString->convertToDatabaseValue([null, null, 'a', 'b'], $PlatformMockStub));

        $result = $this->TypeString->convertToPHPValue('||a|b', $PlatformMockStub);
        $this->assertEquals([null, null, 'a', 'b'], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }
}
