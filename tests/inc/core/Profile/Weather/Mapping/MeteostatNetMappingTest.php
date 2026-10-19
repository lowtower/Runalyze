<?php

declare(strict_types=1);

namespace Runalyze\Tests\Profile\Weather\Mapping;

use Runalyze\Profile\Weather\Mapping\MeteostatNetMapping;
use Runalyze\Profile\Weather\WeatherConditionProfile;

final class MeteostatNetMappingTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('conditionProvider')]
    public function testMapping(int $meteostatCode, int $expected): void
    {
        $mapping = new MeteostatNetMapping();

        $this->assertSame($expected, $mapping->toInternal($meteostatCode));
    }

    /**
     * @return \Iterator<int, array<int>>
     */
    public static function conditionProvider(): \Iterator
    {
        yield [1, WeatherConditionProfile::SUNNY];
        yield [2, WeatherConditionProfile::FAIR];
        yield [3, WeatherConditionProfile::CLOUDY];
        yield [4, WeatherConditionProfile::CHANGEABLE];
        yield [5, WeatherConditionProfile::FOGGY];
        yield [11, WeatherConditionProfile::HEAVYRAIN];
        yield [20, WeatherConditionProfile::SNOWING];
        yield [25, WeatherConditionProfile::THUNDERSTORM];
        yield [27, WeatherConditionProfile::WINDY];
        yield [999, WeatherConditionProfile::UNKNOWN];
    }
}
