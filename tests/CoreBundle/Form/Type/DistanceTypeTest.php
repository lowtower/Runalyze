<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Runalyze\Metrics\Distance\Unit\Kilometer;

final class DistanceTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DistanceType
     */
    protected $Type;

    protected function setUp(): void
    {
        $this->Type = new DistanceType(new Kilometer());
    }

    public function testTransform(): void
    {
        $this->assertSame('', $this->Type->transform(null));

        // Exactly three decimals are displayed.
        $this->assertSame('1.000', $this->Type->transform(1.0));
        $this->assertSame('1.200', $this->Type->transform(1.2));
        $this->assertSame('1.230', $this->Type->transform(1.23));

        // A third existing decimal is preserved.
        $this->assertSame('1.234', $this->Type->transform(1.234));

        // Input precision is limited to three decimal places.
        $this->assertSame('1.235', $this->Type->transform(1.2346));

        // Formatting preserves trailing zeros.
        $this->assertSame('1234.500', $this->Type->transform(1234.5));
    }

    public function testReverseTransform(): void
    {
        $this->assertNull($this->Type->reverseTransform(null));
        $this->assertEqualsWithDelta(1.23, $this->Type->reverseTransform('1.23'), 0.000001);
        $this->assertEqualsWithDelta(1.234, $this->Type->reverseTransform('1.234'), 0.000001);
        $this->assertEqualsWithDelta(7.69, $this->Type->reverseTransform('7,69'), 0.000001);
    }

    public function testRoundingToViewPrecision(): void
    {
        $precision = $this->Type->getViewPrecision();

        $this->assertEqualsWithDelta(1.234, round(1.234, $precision), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1234.50, round(1234.5, $precision), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(7.69, round(7.69, $precision), PHP_FLOAT_EPSILON);
    }

    public function testTransformWithViewPrecision(): void
    {
        $precision = $this->Type->getViewPrecision();

        $this->assertEquals(number_format(1.23, $precision, '.', ''), $this->Type->transform(1.23));
        $this->assertEquals(number_format(1234.5, $precision, '.', ''), $this->Type->transform(1234.5));
        $this->assertEquals(number_format(7.69, $precision, '.', ''), $this->Type->transform(7.69));
    }

    public function testTransformUsesCalculatedViewPrecision(): void
    {
        $values = [1.0, 1.2, 1.23, 1.234, 1.2346, 1234.5];

        foreach ($values as $value) {
            $precision = $this->Type->getViewPrecisionFor($value);
            $expected = number_format($value, $precision, '.', '');
            $this->assertSame($expected, $this->Type->transform($value));
        }
    }
}
