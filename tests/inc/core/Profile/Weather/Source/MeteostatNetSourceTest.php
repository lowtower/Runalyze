<?php

declare(strict_types=1);

namespace Runalyze\Tests\Profile\Weather\Source;

use Runalyze\Profile\Weather\Source\MeteostatNet;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;

final class MeteostatNetSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testProfileValues(): void
    {
        $source = new MeteostatNet();

        $this->assertSame(WeatherSourceProfile::METEOSTAT_NET, $source->getInternalProfileEnum());

        $this->assertSame('meteostat.net', $source->getAttributionLabel($this->createStub(\Symfony\Contracts\Translation\TranslatorInterface::class)));

        $this->assertSame('https://meteostat.net/', $source->getAttributionUrl());
    }
}
