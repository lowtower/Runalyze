<?php

declare(strict_types=1);

namespace Runalyze\Configuration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class FormularFieldWithUnitAndSizeFixture extends \FormularField
{
    public ?string $capturedUnit = null;

    public ?string $capturedSize = null;

    public function setUnit(string $unit): void
    {
        $this->capturedUnit = $unit;
    }

    public function setSize(string $size): void
    {
        $this->capturedSize = $size;
    }

    protected function getFieldCode(): string
    {
        return '';
    }
}

class FormularFieldWithoutUnitOrSizeFixture extends \FormularField
{
    protected function getFieldCode(): string
    {
        return '';
    }
}

/**
 * Characterization test for FieldFactory.
 */
#[Group('default')]
final class FieldFactoryTest extends TestCase
{
    private FieldFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FieldFactory();
    }

    public function testSetAttributesToFieldCallsSetUnitWhenPresentAndOptionNonEmpty(): void
    {
        $field = new FormularFieldWithUnitAndSizeFixture('key', 'label');
        $options = [
            'unit' => 'km',
            'size' => '',
            'css' => '',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertSame('km', $field->capturedUnit);
    }

    public function testSetAttributesToFieldSkipsSetUnitWhenMethodDoesNotExist(): void
    {
        $field = new FormularFieldWithoutUnitOrSizeFixture('key', 'label');
        $options = [
            'unit' => 'km',
            'size' => '',
            'css' => '',
            'layout' => '',
        ];

        // Must not throw, even though setUnit() does not exist on this fixture.
        $this->callSetAttributesToField($field, $options);

        $this->assertTrue(true);
    }

    public function testSetAttributesToFieldSkipsSetUnitWhenOptionIsEmpty(): void
    {
        $field = new FormularFieldWithUnitAndSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => '',
            'css' => '',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertNull($field->capturedUnit);
    }

    public function testSetAttributesToFieldCallsSetSizeWhenPresentAndOptionNonEmpty(): void
    {
        $field = new FormularFieldWithUnitAndSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => 'small',
            'css' => '',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertSame('small', $field->capturedSize);
    }

    public function testSetAttributesToFieldSkipsSetSizeWhenMethodDoesNotExist(): void
    {
        $field = new FormularFieldWithoutUnitOrSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => 'small',
            'css' => '',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertTrue(true);
    }

    public function testSetAttributesToFieldAddsLayoutClassWhenCssNonEmpty(): void
    {
        $field = new FormularFieldWithoutUnitOrSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => '',
            'css' => 'my-css-class',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertSame(['my-css-class'], $this->getLayoutClasses($field));
    }

    public function testSetAttributesToFieldSetsLayoutWhenLayoutNonEmpty(): void
    {
        $field = new FormularFieldWithoutUnitOrSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => '',
            'css' => '',
            'layout' => 'w50',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertSame('w50', $this->getLayout($field));
    }

    public function testSetAttributesToFieldDoesNothingForAllEmptyOptions(): void
    {
        $field = new FormularFieldWithUnitAndSizeFixture('key', 'label');
        $options = [
            'unit' => '',
            'size' => '',
            'css' => '',
            'layout' => '',
        ];

        $this->callSetAttributesToField($field, $options);

        $this->assertNull($field->capturedUnit);
        $this->assertNull($field->capturedSize);
        $this->assertSame('', $this->getLayout($field));
        $this->assertSame([], $this->getLayoutClasses($field));
    }

    private function getLayout(\FormularField $field): string
    {
        $property = new \ReflectionProperty(\FormularField::class, 'layout');

        return $property->getValue($field);
    }

    private function getLayoutClasses(\FormularField $field): array
    {
        $property = new \ReflectionProperty(\FormularField::class, 'layoutClasses');

        return $property->getValue($field);
    }

    /**
     * @param array<string, string> $options
     */
    private function callSetAttributesToField(\FormularField $field, array &$options): void
    {
        $closure = \Closure::bind(
            function (\FormularField $field, array &$options): void {
                $this->setAttributesToField($field, $options);
            },
            $this->factory,
            FieldFactory::class
        );

        $closure($field, $options);
    }
}
