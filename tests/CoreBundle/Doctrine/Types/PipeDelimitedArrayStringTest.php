<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PipeDelimitedArrayStringTest extends TestCase
{
    private PipeDelimitedArrayString $type;

    private AbstractPlatform&Stub $platform;

    protected function setUp(): void
    {
        $type = PipeDelimitedArrayString::getType(PipeDelimitedArrayString::PIPE_ARRAY);
        $this->assertInstanceOf(PipeDelimitedArrayString::class, $type);

        $this->type = $type;
        $this->platform = $this->createStub(AbstractPlatform::class);
    }

    public function testTypeNameDiffersFromTheNumericArrayType(): void
    {
        $this->assertNotSame(PipeDelimitedArray::PIPE_ARRAY, PipeDelimitedArrayString::PIPE_ARRAY);
    }

    public function testElementsAreKeptAsStrings(): void
    {
        $this->assertSame(['a', 'b', '3.4'], $this->type->convertToPHPValue('a|b|3.4', $this->platform));
    }

    public function testEmptyElementsBecomeNull(): void
    {
        $this->assertSame(['a', null, 'b'], $this->type->convertToPHPValue('a||b', $this->platform));
    }

    public function testPipeInsideAnElementIsReplacedByASpace(): void
    {
        $this->assertSame('a|b c', $this->type->convertToDatabaseValue(['a', 'b|c'], $this->platform));
    }

    public function testElementsThatAreNoStringsAreWrittenUnchanged(): void
    {
        $this->assertSame('1|2.5||x', $this->type->convertToDatabaseValue([1, 2.5, null, 'x'], $this->platform));
    }

    public function testTheGivenArrayIsNotChanged(): void
    {
        $value = ['a', 'b|c'];

        $this->type->convertToDatabaseValue($value, $this->platform);

        $this->assertSame(['a', 'b|c'], $value);
    }

    public function testEverythingExceptAFilledArrayIsStoredAsNull(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToDatabaseValue([], $this->platform));
        $this->assertNull($this->type->convertToDatabaseValue('text', $this->platform));
    }

    public function testSqlDeclarationIsTheTextDeclarationOfThePlatform(): void
    {
        $this->platform->method('getClobTypeDeclarationSQL')
            ->willReturn('LONGTEXT');

        $this->assertSame('LONGTEXT', $this->type->getSQLDeclaration([], $this->platform));
    }
}
