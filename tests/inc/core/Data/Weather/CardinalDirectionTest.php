<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

final class CardinalDirectionTest extends \PHPUnit\Framework\TestCase
{
    public function testNonNumericValue(): void
    {
        $this->expectException(\TypeError::class);
        new CardinalDirection('foobar');
    }

    public function testValue(): void
    {
        $Direction = new CardinalDirection(90.5);

        $this->assertEqualsWithDelta(90.5, $Direction->value(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(12.3, $Direction->setDegree(12.3)->value(), PHP_FLOAT_EPSILON);
    }

    public function testDirections(): void
    {
        $directionsToTest = [
            'N' => [337.5, 359, 0, 10, 22.4],
            'NE' => [22.5, 45, 67.4],
            'E' => [67.5, 80, 112.4],
            'SE' => [112.5, 140, 157.4],
            'S' => [157.5, 180, 202.4],
            'SW' => [202.5, 230, 247.4],
            'W' => [247.5, 260, 292.4],
            'NW' => [292.5, 310, 337.4],
        ];

        foreach ($directionsToTest as $string => $values) {
            foreach ($values as $degrees) {
                $this->assertEquals($string, CardinalDirection::getDirection($degrees), 'Mismatch for ' . $degrees . '°');
            }
        }
    }
}
