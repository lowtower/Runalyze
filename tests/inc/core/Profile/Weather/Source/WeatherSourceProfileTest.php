<?php

declare(strict_types=1);

namespace Runalyze\Tests\Profile\Weather\Source;

use Runalyze\Profile\Weather\Source\SourceInterface;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;

final class WeatherSourceProfileTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllClassesExist(): void
    {
        foreach (WeatherSourceProfile::getEnum() as $enum) {
            /** @var SourceInterface $source */
            $source = WeatherSourceProfile::get($enum);

            $this->assertInstanceOf(SourceInterface::class, $source);
            $this->assertEquals($enum, $source->getInternalProfileEnum());
            $this->assertIsBool($source->requiresAttribution());

            if ($source->hasAttributionUrl()) {
                $this->assertNotEmpty($source->getAttributionUrl());
            }
        }
    }
}
