<?php

declare(strict_types=1);

namespace Runalyze\Dataset;

final class DefaultConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllKeysFromEnumAppearInDefaultConfiguration(): void
    {
        $allKeys = new DefaultConfiguration()->allKeys();

        foreach (Keys::getEnum() as $key) {
            $this->assertContains($key, $allKeys, 'Key ' . $key . ' is missing in default dataset config.');
        }
    }

    public function testThatAllKeysFromDefaultConfigurationAreValid(): void
    {
        $DefaultConfiguration = new DefaultConfiguration();

        foreach ($DefaultConfiguration->allKeys() as $key) {
            $this->assertTrue(Keys::isValidValue($key), 'Key ' . $key . ' is invalid.');
        }
    }
}
