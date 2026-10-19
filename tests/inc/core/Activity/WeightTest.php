<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\WeightUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class WeightTest extends \PHPUnit\Framework\TestCase
{
    private int $OriginalDefaultDecimals;

    protected function setUp(): void
    {
        $this->OriginalDefaultDecimals = Weight::$DefaultDecimals;
    }

    protected function tearDown(): void
    {
        Weight::$DefaultDecimals = $this->OriginalDefaultDecimals;
    }

    public function testConstructor(): void
    {
        $this->assertEquals('75.0', new Weight(75, new WeightUnit(WeightUnit::KG))->string(false, 1));
        $this->assertEquals('165.3', new Weight(75, new WeightUnit(WeightUnit::POUNDS))->string(false, 1));
        $this->assertEquals('11.8', new Weight(75, new WeightUnit(WeightUnit::STONES))->string(false, 1));
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertEqualsWithDelta(10, new Weight(0, new WeightUnit(WeightUnit::KG))->setInPreferredUnit(10)->kg(), 0.1);
        $this->assertEqualsWithDelta(4.54, new Weight(0, new WeightUnit(WeightUnit::POUNDS))->setInPreferredUnit(10)->kg(), 0.1);
        $this->assertEqualsWithDelta(63.5, new Weight(0, new WeightUnit(WeightUnit::STONES))->setInPreferredUnit(10)->kg(), 0.1);
    }

    public function testFromKilograms(): void
    {
        $Weight = new Weight();
        $Weight->set(10);

        $this->assertEqualsWithDelta(10.00, $Weight->kg(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(22.04, $Weight->pounds(), 0.01);
        $this->assertEqualsWithDelta(1.57, $Weight->stones(), 0.01);
    }

    public function testFromPounds(): void
    {
        $Weight = new Weight();
        $Weight->setPounds(10);

        $this->assertEqualsWithDelta(10.00, $Weight->pounds(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.54, $Weight->kg(), 0.01);
        $this->assertEqualsWithDelta(0.71, $Weight->stones(), 0.01);
    }

    public function testFromStones(): void
    {
        $Weight = new Weight();
        $Weight->setStones(10);

        $this->assertEqualsWithDelta(10.00, $Weight->stones(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(63.50, $Weight->kg(), 0.01);
        $this->assertEqualsWithDelta(140.0, $Weight->pounds(), 0.01);
    }

    public function testStaticMethod(): void
    {
        Configuration::General()->weightUnit()->set(WeightUnit::KG);
        $unit = '&nbsp;' . Configuration::General()->weightUnit()->unit();

        $this->assertSame('10.0' . $unit, Weight::format(10, true, 1));
        $this->assertSame('12.346' . $unit, Weight::format(12.3456, true, 3));
        $this->assertEquals('12.3', Weight::format(12.3456, false, 1));
    }

    public function testSetParsesCommaAsDecimalSeparator(): void
    {
        $Weight = new Weight();
        $Weight->set('75,5');

        $this->assertEqualsWithDelta(75.5, $Weight->kg(), PHP_FLOAT_EPSILON);
    }

    public function testSetAcceptsPlainFloat(): void
    {
        $Weight = new Weight();
        $Weight->set(75.5);

        $this->assertEqualsWithDelta(75.5, $Weight->kg(), PHP_FLOAT_EPSILON);
    }

    public function testValueIsAliasForKg(): void
    {
        $Weight = new Weight(80);

        $this->assertSame($Weight->kg(), $Weight->value());
    }

    public function testUnitReturnsKgSymbolForKgSystem(): void
    {
        $Weight = new Weight(80, new WeightUnit(WeightUnit::KG));

        $this->assertSame(WeightUnit::KG, $Weight->unit());
    }

    public function testUnitReturnsPoundsSymbolForPoundsSystem(): void
    {
        $Weight = new Weight(80, new WeightUnit(WeightUnit::POUNDS));

        $this->assertSame(WeightUnit::POUNDS, $Weight->unit());
    }

    public function testUnitReturnsStonesSymbolForStonesSystem(): void
    {
        $Weight = new Weight(80, new WeightUnit(WeightUnit::STONES));

        $this->assertSame(WeightUnit::STONES, $Weight->unit());
    }

    public function testValueInPreferredUnitReturnsKgForKgSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::KG));

        $this->assertEqualsWithDelta(75.0, $Weight->valueInPreferredUnit(), PHP_FLOAT_EPSILON);
    }

    public function testValueInPreferredUnitReturnsPoundsForPoundsSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::POUNDS));

        $this->assertSame($Weight->pounds(), $Weight->valueInPreferredUnit());
    }

    public function testValueInPreferredUnitReturnsStonesForStonesSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::STONES));

        $this->assertSame($Weight->stones(), $Weight->valueInPreferredUnit());
    }

    public function testStringDelegatesToStringKGForKgSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::KG));

        $this->assertSame($Weight->stringKG(), $Weight->string());
    }

    public function testStringDelegatesToStringPoundsForPoundsSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::POUNDS));

        $this->assertSame($Weight->stringPounds(), $Weight->string());
    }

    public function testStringDelegatesToStringStonesForStonesSystem(): void
    {
        $Weight = new Weight(75, new WeightUnit(WeightUnit::STONES));

        $this->assertSame($Weight->stringStones(), $Weight->string());
    }

    public function testStringKGUsesConfiguredDefaultDecimalsWhenNotGiven(): void
    {
        Weight::$DefaultDecimals = 2;

        $Weight = new Weight(75.456);

        $this->assertSame('75.46&nbsp;' . WeightUnit::KG, $Weight->stringKG());
    }
}
