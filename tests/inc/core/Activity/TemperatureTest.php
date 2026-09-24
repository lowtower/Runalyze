<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\TemperatureUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class TemperatureTest extends \PHPUnit\Framework\TestCase
{
    private int $OriginalDefaultDecimals;

    protected function setUp(): void
    {
        $this->OriginalDefaultDecimals = Temperature::$DefaultDecimals;
    }

    protected function tearDown(): void
    {
        Temperature::$DefaultDecimals = $this->OriginalDefaultDecimals;
    }

    public function testConstructor(): void
    {
        $this->assertEquals('15.0', (new Temperature(15, new TemperatureUnit(TemperatureUnit::CELSIUS)))->string(false, 1));
        $this->assertEquals('59.0', (new Temperature(15, new TemperatureUnit(TemperatureUnit::FAHRENHEIT)))->string(false, 1));
    }

    public function testConstructorAlwaysInterpretsInputAsCelsiusRegardlessOfPreferredUnit(): void
    {
        $Temperature = new Temperature(15, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));

        $this->assertSame(15.0, $Temperature->celsius());
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertEqualsWithDelta(15, (new Temperature(0, new TemperatureUnit(TemperatureUnit::CELSIUS)))->setInPreferredUnit(15)->celsius(), 0.1);
        $this->assertEqualsWithDelta(15.00, (new Temperature(0, new TemperatureUnit(TemperatureUnit::FAHRENHEIT)))->setInPreferredUnit(59)->celsius(), 0.1);
    }

    public function testFromCelsius(): void
    {
        $Temperature = new Temperature();
        $Temperature->set(15);

        $this->assertEquals(15.00, $Temperature->celsius());
        $this->assertEqualsWithDelta(59.00, $Temperature->fahrenheit(), 0.01);
    }

    public function testFromFahrenheit(): void
    {
        $Temperature = new Temperature();
        $Temperature->setFahrenheit(59);

        $this->assertEquals(59.00, $Temperature->fahrenheit());
        $this->assertEqualsWithDelta(15, $Temperature->celsius(), 0.01);
    }

    public function testStaticMethod(): void
    {
        Configuration::General()->temperatureUnit()->set(TemperatureUnit::CELSIUS);
        $unit = '&nbsp;' . Configuration::General()->TemperatureUnit()->unit();

        $this->assertEquals('10.0' . $unit, Temperature::format(10, true, 1));
        $this->assertEquals('12.35' . $unit, Temperature::format(12.3456, true, 2));
    }

    public function testEmptyTemperature(): void
    {
        $Temperature = new Temperature();

        $this->assertTrue($Temperature->isEmpty());
        $this->assertNull($Temperature->value());
        $this->assertNull($Temperature->celsius());
        $this->assertNull($Temperature->fahrenheit());
    }

    public function testEmptyTemperatureAfterChanging(): void
    {
        $Temperature = new Temperature(13);
        $Temperature->setInPreferredUnit(null);

        $this->assertTrue($Temperature->isEmpty());
        $this->assertNull($Temperature->value());
        $this->assertNull($Temperature->celsius());
        $this->assertNull($Temperature->fahrenheit());
    }

    public function testSetWithEmptyStringResultsInEmptyTemperature(): void
    {
        $Temperature = new Temperature(13);
        $Temperature->set('');

        $this->assertTrue($Temperature->isEmpty());
    }

    public function testSetFahrenheitWithEmptyStringResultsInEmptyTemperature(): void
    {
        $Temperature = new Temperature(13);
        $Temperature->setFahrenheit('');

        $this->assertTrue($Temperature->isEmpty());
    }

    public function testSetParsesCommaAsDecimalSeparator(): void
    {
        $Temperature = new Temperature();
        $Temperature->set('15,5');

        $this->assertSame(15.5, $Temperature->celsius());
    }

    public function testSetFahrenheitParsesCommaAsDecimalSeparator(): void
    {
        $Temperature = new Temperature();
        $Temperature->setFahrenheit('59,5');

        $this->assertSame(15.28, $Temperature->celsius());
    }

    public function testValueIsAliasForCelsius(): void
    {
        $Temperature = new Temperature(20);

        $this->assertSame($Temperature->celsius(), $Temperature->value());
    }

    public function testUnitReturnsCelsiusSymbolForCelsiusSystem(): void
    {
        $Temperature = new Temperature(20, new TemperatureUnit(TemperatureUnit::CELSIUS));

        $this->assertSame(TemperatureUnit::CELSIUS, $Temperature->unit());
    }

    public function testUnitReturnsFahrenheitSymbolForFahrenheitSystem(): void
    {
        $Temperature = new Temperature(20, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));

        $this->assertSame(TemperatureUnit::FAHRENHEIT, $Temperature->unit());
    }

    public function testValueInPreferredUnitReturnsCelsiusForCelsiusSystem(): void
    {
        $Temperature = new Temperature(15, new TemperatureUnit(TemperatureUnit::CELSIUS));

        $this->assertSame(15.0, $Temperature->valueInPreferredUnit());
    }

    public function testValueInPreferredUnitReturnsFahrenheitForFahrenheitSystem(): void
    {
        $Temperature = new Temperature(15, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));

        $this->assertSame(59.0, $Temperature->valueInPreferredUnit());
    }

    public function testStringCelsiusReturnsEmptyStringWhenEmpty(): void
    {
        $Temperature = new Temperature();

        $this->assertSame('', $Temperature->stringCelsius());
    }

    public function testStringFahrenheitReturnsEmptyStringWhenEmpty(): void
    {
        $Temperature = new Temperature();

        $this->assertSame('', $Temperature->stringFahrenheit());
    }

    public function testStringReturnsEmptyStringWhenEmpty(): void
    {
        $Temperature = new Temperature(null, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));

        $this->assertSame('', $Temperature->string());
    }

    public function testStringCelsiusUsesDefaultDecimalsWhenNotGiven(): void
    {
        Temperature::$DefaultDecimals = 0;

        $Temperature = new Temperature(15.678);

        $this->assertSame('16&nbsp;' . TemperatureUnit::CELSIUS, $Temperature->stringCelsius());
    }

    public function testStringFahrenheitUsesDefaultDecimalsWhenNotGiven(): void
    {
        Temperature::$DefaultDecimals = 0;

        $Temperature = new Temperature(15);

        $this->assertSame('59&nbsp;' . TemperatureUnit::FAHRENHEIT, $Temperature->stringFahrenheit());
    }

    public function testStringDelegatesToStringCelsiusForCelsiusSystem(): void
    {
        $Temperature = new Temperature(15, new TemperatureUnit(TemperatureUnit::CELSIUS));

        $this->assertSame($Temperature->stringCelsius(), $Temperature->string());
    }

    public function testStringDelegatesToStringFahrenheitForFahrenheitSystem(): void
    {
        $Temperature = new Temperature(15, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));

        $this->assertSame($Temperature->stringFahrenheit(), $Temperature->string());
    }
}
