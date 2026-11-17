<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\DistanceUnitSystem;

#[Group('plugin')]
final class PluginConfigurationValueFormFieldFactoryTest extends TestCase
{
    private PluginConfigurationValueFormFieldFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new PluginConfigurationValueFormFieldFactory();
    }

    public function testBaseValueCreatesMiddleSizedInput(): void
    {
        $field = $this->factory->create(new PluginConfigurationValue('key', 'Label', '', 'value'));

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString(FormularInput::$SIZE_MIDDLE, $field->getCode());
    }

    public function testIntegerValueCreatesSmallInput(): void
    {
        $field = $this->factory->create(new PluginConfigurationValueInt('key', 'Label', '', 12));

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString(FormularInput::$SIZE_SMALL, $field->getCode());
    }

    public function testFloatValueCreatesSmallInput(): void
    {
        $field = $this->factory->create(new PluginConfigurationValueFloat('key', 'Label', '', 1.5));

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString(FormularInput::$SIZE_SMALL, $field->getCode());
    }

    public function testArrayValueCreatesFullWidthInputWithMaximumLength(): void
    {
        $field = $this->factory->create(new PluginConfigurationValueArray('key', 'Label', '', ['one']));

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString(FormularInput::$SIZE_FULL_INLINE, $field->getCode());
        $this->assertStringContainsString('maxlength="255"', $field->getCode());
    }

    public function testBooleanValueCreatesCheckbox(): void
    {
        $field = $this->factory->create(new PluginConfigurationValueBool('key', 'Label', '', true));

        $this->assertInstanceOf(FormularCheckbox::class, $field);
    }

    public function testSelectValueCreatesSelectBoxWithConfiguredOptions(): void
    {
        $value = new PluginConfigurationValueSelect('key', 'Label', '', 'first');
        $value->setOptions([
            'first' => 'First option',
            'second' => 'Second option',
        ]);

        $field = $this->factory->create($value);

        $this->assertInstanceOf(FormularSelectBox::class, $field);
        $this->assertStringContainsString('First option', $field->getCode());
        $this->assertStringContainsString('Second option', $field->getCode());
    }

    public function testHiddenArrayDoesNotCreateAField(): void
    {
        $this->assertNull(
            $this->factory->create(new PluginConfigurationValueHiddenArray('key'))
        );
    }

    public function testTooltipIsIncludedInFieldLabel(): void
    {
        $field = $this->factory->create(new PluginConfigurationValue('key', 'Label', 'Tooltip', 'value'));
        $this->assertInstanceOf(\FormularField::class, $field);

        $this->assertStringContainsString('Tooltip', $field->getCode());
    }

    public function testDistanceValueUsesLegacyMilesLabelForImperialUnits(): void
    {
        $value = new PluginConfigurationValueDistance('distance', '', '', 0);
        $this->setUnitSystem($value, DistanceUnitSystem::IMPERIAL);

        $field = $this->factory->create($value);

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString(FormularInput::$SIZE_SMALL, $field->getCode());
        $this->assertStringContainsString('miles', $field->getCode());
    }

    public function testDistancesValueUsesLegacyMilesLabelForImperialUnits(): void
    {
        $value = new PluginConfigurationValueDistances('distances');
        $this->setUnitSystem($value, DistanceUnitSystem::IMPERIAL);

        $field = $this->factory->create($value);

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString('miles', $field->getCode());
    }

    public function testDistancesValueUsesLegacyKilometerLabelForMetricUnits(): void
    {
        $value = new PluginConfigurationValueDistances('distances');
        $this->setUnitSystem($value, DistanceUnitSystem::METRIC);

        $field = $this->factory->create($value);

        $this->assertInstanceOf(FormularInput::class, $field);
        $this->assertStringContainsString('km', $field->getCode());
        $this->assertStringContainsString(FormularInput::$SIZE_FULL_INLINE, $field->getCode());
        $this->assertStringContainsString('maxlength="255"', $field->getCode());
    }

    private function setUnitSystem(
        PluginConfigurationValueDistance|PluginConfigurationValueDistances $value,
        string $system
    ): void {
        $property = new ReflectionProperty($value, 'UnitSystem');
        $property->setValue($value, new DistanceUnitSystem($system));
    }
}
