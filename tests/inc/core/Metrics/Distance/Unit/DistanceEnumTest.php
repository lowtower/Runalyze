<?php

namespace Runalyze\Tests\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\UnitInterface;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Metrics\Distance\Unit\DistanceEnum;

class DistanceEnumTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsCanBeConstructed(): void
    {
        $units = DistanceEnum::getEnum();
        foreach ($units as $value) {
            $unit = DistanceEnum::get($value);
            $this->assertInstanceOf(AbstractDistanceUnit::class, $unit, "Unit for value {$value} should be an instance of AbstractDistanceUnit.");
            $this->assertInstanceOf(UnitInterface::class, $unit, "Unit for value {$value} should implement UnitInterface.");
        }
    }

    public function testGetEnumReturnsAllUnits(): void
    {
        $units = DistanceEnum::getEnum();
        $expectedUnits = [
            'KILOMETER' => DistanceEnum::KILOMETER,
            'MILES' => DistanceEnum::MILES,
            'METER' => DistanceEnum::METER,
            'YARDS' => DistanceEnum::YARDS,
            'CENTIMETER' => DistanceEnum::CENTIMETER,
            'FEET' => DistanceEnum::FEET,
        ];

        $this->assertCount(count($expectedUnits), $units, 'getEnum() should return all units.');
        $this->assertEquals($expectedUnits, $units, 'getEnum() should return the correct units and values.');
    }

    public function testIsValidName(): void
    {
        $this->assertTrue(DistanceEnum::isValidName('KILOMETER'), "isValidName() should return true for 'KILOMETER'.");
        $this->assertTrue(DistanceEnum::isValidName('MILES'), "isValidName() should return true for 'MILES'.");
        $this->assertFalse(DistanceEnum::isValidName('INVALID'), "isValidName() should return false for 'INVALID'.");
    }

    public function testIsValidValue(): void
    {
        $this->assertTrue(DistanceEnum::isValidValue(DistanceEnum::KILOMETER), 'isValidValue() should return true for KILOMETER.');
        $this->assertTrue(DistanceEnum::isValidValue(DistanceEnum::MILES), 'isValidValue() should return true for MILES.');
        $this->assertFalse(DistanceEnum::isValidValue(999), 'isValidValue() should return false for invalid value.');
    }
}
