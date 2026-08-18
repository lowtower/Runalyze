<?php

namespace Runalyze\Tests\Profile\Weather\Mapping;

use Runalyze\Profile\Weather\Mapping\MeteostatNetMapping;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class MeteostatNetMappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider conditionProvider
     */
    public function testMapping($meteostatCode, $expected)
    {
        $mapping = new MeteostatNetMapping();

        $this->assertEquals($expected, $mapping->toInternal($meteostatCode));
    }

    /**
     * @return array<int, int[]>
     */
    public function conditionProvider(): array
    {
        return [
            [1, WeatherConditionProfile::SUNNY],
            [2, WeatherConditionProfile::FAIR],
            [3, WeatherConditionProfile::CLOUDY],
            [4, WeatherConditionProfile::CHANGEABLE],
            [5, WeatherConditionProfile::FOGGY],
            [11, WeatherConditionProfile::HEAVYRAIN],
            [20, WeatherConditionProfile::SNOWING],
            [25, WeatherConditionProfile::THUNDERSTORM],
            [27, WeatherConditionProfile::WINDY],
            [999, WeatherConditionProfile::UNKNOWN]
        ];
    }
}
