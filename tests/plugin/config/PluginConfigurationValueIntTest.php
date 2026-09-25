<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('plugin')]
class PluginConfigurationValueIntTest extends TestCase
{
    #[DataProvider('provideStringValues')]
    public function testSetValueFromStringCastsToInt(string $input, int $expected): void
    {
        $Value = new PluginConfigurationValueInt('key');
        $Value->setValueFromString($input);

        $this->assertSame($expected, $Value->value());
    }

    /**
     * @return array<string, string[]|int[]>
     */
    public static function provideStringValues(): array
    {
        return [
            'plain integer string' => ['12', 12],
            'decimal string truncates towards zero' => ['12.9', 12],
            'negative decimal string truncates towards zero' => ['-5.7', -5],
            'leading zeros are stripped' => ['007', 7],
            'non-numeric string becomes zero' => ['abc', 0],
            'empty string becomes zero' => ['', 0],
        ];
    }

    public function testValueAsStringReturnsCastValueAsString(): void
    {
        $Value = new PluginConfigurationValueInt('key');
        $Value->setValueFromString('42.7');

        $this->assertSame('42', $Value->valueAsString());
    }

    public function testValueAsStringIsSafeImmediatelyAfterConstructionUsingDefaultValue(): void
    {
        $Value = new PluginConfigurationValueInt('key', '', '', 5);

        $this->assertSame('5', $Value->valueAsString());
    }

    public function testValueAsStringIsSafeImmediatelyAfterConstructionWithBaseClassDefault(): void
    {
        $Value = new PluginConfigurationValue('key');

        $this->assertSame('', $Value->valueAsString());
    }

    public function testValueAsStringWorksAfterSetDefaultValueAsValue(): void
    {
        $Value = new PluginConfigurationValueInt('key', '', '', 5);
        $Value->setDefaultValueAsValue();

        $this->assertSame('5', $Value->valueAsString());
    }
}
