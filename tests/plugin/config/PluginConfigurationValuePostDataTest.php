<?php

declare(strict_types=1);

use PHPUnit\\Framework\\Attributes\\Group;
use PHPUnit\\Framework\\TestCase;
use Runalyze\\Bundle\\CoreBundle\\Services\\PluginConfigurationStorageInterface;

#[Group('plugin')]
final class PluginConfigurationValuePostDataTest extends TestCase
{
    public function testBooleanValueIsTrueWhenItsKeyIsPresent(): void
    {
        $value = new PluginConfigurationValueBool('enabled');
        $value->setValueFromPost(['enabled' => '0']);

        $this->assertTrue($value->value());
    }

    public function testBooleanValueIsFalseWhenItsKeyIsMissing(): void
    {
        $value = new PluginConfigurationValueBool('enabled', '', '', true);
        $value->setValueFromPost([]);

        $this->assertFalse($value->value());
    }

    public function testArrayValueIsParsedFromExplicitPostData(): void
    {
        $value = new PluginConfigurationValueArray('items');
        $value->setValueFromPost(['items' => 'first, second']);

        $this->assertSame(['first', 'second'], $value->value());
    }

    public function testArrayValueIsUnchangedWhenItsKeyIsMissing(): void
    {
        $value = new PluginConfigurationValueArray('items', '', '', ['existing']);
        $value->setValueFromPost([]);

        $this->assertSame(['existing'], $value->value());
    }

    public function testConfigurationPassesPostDataToValuesAndPersistsThem(): void
    {
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function (int $pluginId, string $key, string $value): void {
                $this->assertSame(42, $pluginId);

                if ($key === 'name') {
                    $this->assertSame('updated', $value);
                } else {
                    $this->assertSame('1', $value);
                }
            });

        $configuration = new PluginConfiguration(42, $storage);
        $configuration->addValue(new PluginConfigurationValue('name'));
        $configuration->addValue(new PluginConfigurationValueBool('enabled'));

        $configuration->updateFromPost([
            'name' => 'updated',
            'enabled' => '1',
        ]);

        $this->assertSame('updated', $configuration->value('name'));
        $this->assertTrue($configuration->value('enabled'));
    }
}
