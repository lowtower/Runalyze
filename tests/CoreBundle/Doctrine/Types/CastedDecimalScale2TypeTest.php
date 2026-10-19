<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\CastedDecimalScale2Type;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class CastedDecimalScale2TypeTest extends TestCase
{
    private CastedDecimalScale2Type $type;

    private AbstractPlatform&Stub $platform;

    protected function setUp(): void
    {
        $type = CastedDecimalScale2Type::getType(CastedDecimalScale2Type::CASTED_DECIMAL_2);
        $this->assertInstanceOf(CastedDecimalScale2Type::class, $type);

        $this->type = $type;
        $this->platform = $this->createStub(AbstractPlatform::class);
    }

    public function testNullStaysNullInBothDirections(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    #[DataProvider('valuesForTheDatabase')]
    public function testDatabaseValueHasExactlyTwoDecimals(int|float|string $value, string $expected): void
    {
        $this->assertSame($expected, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    public static function valuesForTheDatabase(): iterable
    {
        yield 'float with many decimals' => [3.14159, '3.14'];
        yield 'integer' => [2, '2.00'];
        yield 'numeric string' => ['7.5', '7.50'];
        yield 'zero' => [0, '0.00'];
        yield 'negative' => [-4.0, '-4.00'];
    }

    #[DataProvider('valuesFromTheDatabase')]
    public function testPhpValueIsAlwaysAFloat(string $value, float $expected): void
    {
        $this->assertSame($expected, $this->type->convertToPHPValue($value, $this->platform));
    }

    public static function valuesFromTheDatabase(): iterable
    {
        yield 'decimal' => ['12.25', 12.25];
        yield 'integer text' => ['3', 3.0];
        yield 'zero' => ['0.00', 0.0];
    }

    public function testSqlDeclarationIsTheDecimalDeclarationOfThePlatform(): void
    {
        $this->platform->method('getDecimalTypeDeclarationSQL')
            ->willReturn('NUMERIC(5, 2)');

        $this->assertSame('NUMERIC(5, 2)', $this->type->getSQLDeclaration([
            'precision' => 5,
            'scale' => 2,
        ], $this->platform));
    }
}
