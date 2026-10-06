<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;

class PipeDelimitedArrayTest extends \PHPUnit\Framework\TestCase
{
    protected PipeDelimitedArray $TypeNumber;

    protected PipeDelimitedArrayString $TypeString;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected \PHPUnit\Framework\MockObject\Stub $PlatformMock;

    protected function setUp(): void
    {
        /** @var PipeDelimitedArray $typeNumber */
        $typeNumber = PipeDelimitedArray::getType(PipeDelimitedArray::PIPE_ARRAY);
        $this->TypeNumber = $typeNumber;
        /** @var PipeDelimitedArrayString $typeString */
        $typeString = PipeDelimitedArrayString::getType(PipeDelimitedArrayString::PIPE_ARRAY);
        $this->TypeString = $typeString;
        $this->PlatformMock = $this->createStub(AbstractPlatform::class);
    }

    public function testEmptyData(): void
    {
        $this->assertNull($this->TypeNumber->convertToPHPValue(null, $this->PlatformMock));
        $this->assertNull($this->TypeNumber->convertToPHPValue('', $this->PlatformMock));
        $this->assertNull($this->TypeNumber->convertToDatabaseValue([], $this->PlatformMock));

        $this->assertNull($this->TypeString->convertToPHPValue(null, $this->PlatformMock));
        $this->assertNull($this->TypeString->convertToPHPValue('', $this->PlatformMock));
        $this->assertNull($this->TypeString->convertToDatabaseValue([], $this->PlatformMock));
    }

    public function testSimpleData(): void
    {
        // numeric
        $r = $this->TypeNumber->convertToPHPValue('3.14|42|0', $this->PlatformMock);
        $this->assertEquals([3.14, 42, 0], $r);
        $this->assertIsNotString($r[0]);
        $this->assertIsNumeric($r[1]);
        $this->assertIsNotString($r[2]);

        $this->assertSame('3.14|42|0', $this->TypeNumber->convertToDatabaseValue([3.14, 42, 0], $this->PlatformMock));

        // string
        $this->assertEquals(['a', 'b', 3.4], $this->TypeString->convertToPHPValue('a|b|3.4', $this->PlatformMock));
        $this->assertSame('a|b|3.4', $this->TypeString->convertToDatabaseValue(['a', 'b', 3.4], $this->PlatformMock));

        // | will be change to blank
        $this->assertSame('a|b c', $this->TypeString->convertToDatabaseValue(['a', 'b|c'], $this->PlatformMock));
    }

    public function testPartiallyEmptyData(): void
    {
        $this->assertSame('||-12.3|-11.4|-9.8', $this->TypeNumber->convertToDatabaseValue([null, null, -12.3, -11.4, -9.8], $this->PlatformMock));

        $result = $this->TypeNumber->convertToPHPValue('||-12.3|-11.4|-9.8', $this->PlatformMock);
        $this->assertEquals([null, null, -12.3, -11.4, -9.8], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);

        $this->assertSame('||a|b', $this->TypeString->convertToDatabaseValue([null, null, 'a', 'b'], $this->PlatformMock));

        $result = $this->TypeString->convertToPHPValue('||a|b', $this->PlatformMock);
        $this->assertEquals([null, null, 'a', 'b'], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }
}
