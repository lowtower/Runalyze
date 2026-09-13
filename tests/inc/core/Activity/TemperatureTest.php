<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\TemperatureUnit;

class TemperatureTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $this->assertEquals('15.0', (new Temperature(15, new TemperatureUnit(TemperatureUnit::CELSIUS)))->string(false, 1));
        $this->assertEquals('59.0', (new Temperature(15, new TemperatureUnit(TemperatureUnit::FAHRENHEIT)))->string(false, 1));
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
}
