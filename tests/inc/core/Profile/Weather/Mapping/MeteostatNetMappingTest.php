<?php

namespace Runalyze\Tests\Profile\Weather\Mapping;

use Runalyze\Profile\Weather\Mapping\MeteostatNetMapping;
use Runalyze\Profile\Weather\WeatherConditionProfile;

class MeteostatNetMappingTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('conditionProvider')]
    public function testMapping(int $meteostatCode, int $expected): void
    {
        $mapping = new MeteostatNetMapping();

        $this->assertEquals($expected, $mapping->toInternal($meteostatCode));
    }

    /**
     * @return array<int, int[]>
     */
    public static function conditionProvider(): array
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
            [999, WeatherConditionProfile::UNKNOWN],
        ];
    }
}
