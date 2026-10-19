<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use Runalyze\Bundle\CoreBundle\Twig\UtilityExtension;

final class UtilityExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected UtilityExtension $Utility;

    protected function setUp(): void
    {
        $this->Utility = new UtilityExtension();
    }

    public function testDuration(): void
    {
        $this->assertSame('0:09.58', $this->Utility->duration(9.579, 2, '.'));
        $this->assertSame('2:41,03', $this->Utility->duration(161.03, 2, ','));
        $this->assertSame('0:13', $this->Utility->duration(13));
        $this->assertSame('22:07', $this->Utility->duration(1327));
        $this->assertSame('59:59', $this->Utility->duration(3599));
        $this->assertSame('1:00:01', $this->Utility->duration(3601));
        $this->assertSame('3:35:47', $this->Utility->duration(12947));
        $this->assertSame('1d 01:03:05', $this->Utility->duration(90185));
    }

    public function testFilesizeAsString(): void
    {
        $this->assertSame('3 B', $this->Utility->filesizeAsString(3.14));
        $this->assertSame('1.00 kB', $this->Utility->filesizeAsString(1024));
        $this->assertSame('2.35 MB', $this->Utility->filesizeAsString(2.345 * 1024 ** 2));
        $this->assertSame('1.20 GB', $this->Utility->filesizeAsString(1.2 * 1024 ** 3));
        $this->assertSame('271.30 GB', $this->Utility->filesizeAsString(271.3 * 1024 ** 3));
        $this->assertSame('8.02 TB', $this->Utility->filesizeAsString(8.02 * 1024 ** 4));
        $this->assertSame('6.33 PB', $this->Utility->filesizeAsString(6.33 * 1024 ** 5));
        $this->assertSame('7.41 EB', $this->Utility->filesizeAsString(7.41 * 1024 ** 6));
        $this->assertSame('5.90 ZB', $this->Utility->filesizeAsString(5.90 * 1024 ** 7));
        $this->assertSame('1.00 YB', $this->Utility->filesizeAsString(1.00 * 1024 ** 8));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideValuesAroundIntMax')]
    public function testFilesizeAsStringAroundIntMaxBoundary(float $bytes, string $expected): void
    {
        $this->assertSame($expected, $this->Utility->filesizeAsString($bytes));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideZeroOrNearZeroValues')]
    public function testFilesizeAsStringForZeroValues(float $bytes): void
    {
        $this->assertSame('0 B', $this->Utility->filesizeAsString($bytes));
    }

    /**
     * @return \Iterator<string, array<float>>
     */
    public static function provideZeroOrNearZeroValues(): \Iterator
    {
        yield 'exactly 0' => [0.0];
        yield 'negative close to 0' => [-0.5];
        yield 'positive, below 1 Byte' => [0.99];
    }

    /**
     * @return \Iterator<string, array<(float|string)>>
     */
    public static function provideValuesAroundIntMax(): \Iterator
    {
        yield 'close below PHP_INT_MAX (~8 EB)' => [
            (float) PHP_INT_MAX - 1024 ** 6,
            '7.00 EB',
        ];
        yield 'exactly PHP_INT_MAX (~8 EB)' => [
            (float) PHP_INT_MAX,
            '8.00 EB',
        ];
        yield 'close above PHP_INT_MAX (~8 EB)' => [
            (float) PHP_INT_MAX + 1024 ** 5,
            '8.00 EB',
        ];
        yield '5.90 ZB (faar above PHP_INT_MAX)' => [
            5.90 * 1024 ** 7,
            '5.90 ZB',
        ];
        yield '1.00 YB' => [
            1.00 * 1024 ** 8,
            '1.00 YB',
        ];
        yield '9.99 YB (extremely large value)' => [
            9.99 * 1024 ** 8,
            '9.99 YB',
        ];
        yield 'Value above YB (array limit)' => [
            1024.0 * 1024 ** 8,
            '1024.00 YB',
        ];
    }
}
