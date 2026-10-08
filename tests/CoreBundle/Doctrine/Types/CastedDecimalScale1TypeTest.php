<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\CastedDecimalScale1Type;

#[\PHPUnit\Framework\Attributes\Group('default')]
class CastedDecimalScale1TypeTest extends TestCase
{
    private CastedDecimalScale1Type $type;

    private AbstractPlatform&Stub $platform;

    protected function setUp(): void
    {
        $type = CastedDecimalScale1Type::getType(CastedDecimalScale1Type::CASTED_DECIMAL_1);
        $this->assertInstanceOf(CastedDecimalScale1Type::class, $type);

        $this->type = $type;
        $this->platform = $this->createStub(AbstractPlatform::class);
    }

    public function testNullStaysNullInBothDirections(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    #[DataProvider('valuesForTheDatabase')]
    public function testDatabaseValueHasExactlyOneDecimal(int|float|string $value, string $expected): void
    {
        $this->assertSame($expected, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    public static function valuesForTheDatabase(): iterable
    {
        yield 'float with many decimals' => [3.14159, '3.1'];
        yield 'integer' => [2, '2.0'];
        yield 'numeric string' => ['7.5', '7.5'];
        yield 'zero' => [0, '0.0'];
        yield 'negative' => [-4.0, '-4.0'];
    }

    #[DataProvider('valuesFromTheDatabase')]
    public function testPhpValueIsAlwaysAFloat(string $value, float $expected): void
    {
        $this->assertSame($expected, $this->type->convertToPHPValue($value, $this->platform));
    }

    public static function valuesFromTheDatabase(): iterable
    {
        yield 'decimal' => ['12.5', 12.5];
        yield 'integer text' => ['3', 3.0];
        yield 'zero' => ['0.0', 0.0];
    }

    public function testSqlDeclarationIsTheDecimalDeclarationOfThePlatform(): void
    {
        $this->platform->method('getDecimalTypeDeclarationSQL')
            ->willReturn('NUMERIC(3, 1)');

        $this->assertSame('NUMERIC(3, 1)', $this->type->getSQLDeclaration([
            'precision' => 3,
            'scale' => 1,
        ], $this->platform));
    }
}
