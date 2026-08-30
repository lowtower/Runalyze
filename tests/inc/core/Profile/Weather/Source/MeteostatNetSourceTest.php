<?php

namespace Runalyze\Tests\Profile\Weather\Source;

use Runalyze\Profile\Weather\Source\MeteostatNet;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;

class MeteostatNetSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testProfileValues()
    {
        $source = new MeteostatNet();

        $this->assertEquals(WeatherSourceProfile::METEOSTAT_NET, $source->getInternalProfileEnum());

        $this->assertEquals('meteostat.net', $source->getAttributionLabel($this->createMock(\Symfony\Contracts\Translation\TranslatorInterface::class)));

        $this->assertEquals('https://meteostat.net/', $source->getAttributionUrl());
    }
}
