<?php

declare(strict_types=1);

namespace Runalyze\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class EntityTestDouble extends Entity
{
    public function __construct(array $data = [], bool $checkArraySizes = false)
    {
        $this->checkArraySizes = $checkArraySizes;

        parent::__construct($data);
    }

    public function properties(): array
    {
        return ['scalar', 'arr', 'arr2', 'nullable', 'readonly'];
    }

    public function isArray(string $key): bool
    {
        return $key === 'arr' || $key === 'arr2';
    }

    protected function canBeNull(string $key): bool
    {
        return $key === 'nullable';
    }

    protected function canSet(string $key): bool
    {
        return $key !== 'readonly';
    }

    public function callEnsureNumericValue(array|string $keyOrKeys, int|float $defaultValue = 0): void
    {
        $this->ensureNumericValue($keyOrKeys, $defaultValue);
    }

    public function callEnsureNullIfEmpty(array|string $keyOrKeys, bool $ensureNumericIfNotEmpty = false, bool $treatZeroAsNull = false): void
    {
        $this->ensureNullIfEmpty($keyOrKeys, $ensureNumericIfNotEmpty, $treatZeroAsNull);
    }
}

class IgnoringScalarEntityTestDouble extends EntityTestDouble
{
    protected function ignoreNonEmptyValue(string $key): bool
    {
        return $key === 'scalar';
    }
}

#[Group('default')]
class EntityTest extends TestCase
{
    public function testEmptyConstructorAppliesDefaultsForEachPropertyType(): void
    {
        $entity = new EntityTestDouble();

        self::assertSame('', $entity->get('scalar'));
        self::assertSame([], $entity->get('arr'));
        self::assertSame([], $entity->get('arr2'));
        self::assertNull($entity->get('nullable'));
        self::assertSame('', $entity->get('readonly'));
    }

    public function testProvidedScalarValueIsUsedAsIs(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => 'hello',
        ]);

        self::assertSame('hello', $entity->get('scalar'));
    }

    public function testProvidedEmptyStringIsTreatedAsNotSetAndFallsBackToDefault(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => '',
        ]);

        self::assertSame('', $entity->get('scalar'));
    }

    public function testExplicitNullForNonNullablePropertyFallsBackToDefaultEmptyString(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => null,
        ]);

        self::assertSame('', $entity->get('scalar'));
    }

    public function testExplicitNullForNullablePropertyIsAcceptedAsDefault(): void
    {
        $entity = new EntityTestDouble([
            'nullable' => null,
        ]);

        self::assertNull($entity->get('nullable'));
    }

    public function testArrayValueGivenAsArrayIsUsedDirectly(): void
    {
        $entity = new EntityTestDouble([
            'arr' => ['a', 'b', 'c'],
        ]);

        self::assertSame(['a', 'b', 'c'], $entity->get('arr'));
    }

    public function testArrayValueGivenAsSeparatedStringIsExploded(): void
    {
        $entity = new EntityTestDouble([
            'arr' => 'a' . Entity::ARRAY_SEPARATOR . 'b' . Entity::ARRAY_SEPARATOR . 'c',
        ]);

        self::assertSame(['a', 'b', 'c'], $entity->get('arr'));
    }

    public function testNumReflectsLargestArrayPropertyWhenSizesDiffer(): void
    {
        $entity = new EntityTestDouble([
            'arr' => ['a', 'b'],
            'arr2' => ['x', 'y', 'z'],
        ]);

        self::assertSame(3, $entity->num());
    }

    public function testCheckArraySizesAcceptsEquallySizedArrays(): void
    {
        $entity = new EntityTestDouble([
            'arr' => ['a', 'b'],
            'arr2' => ['x', 'y'],
        ], checkArraySizes: true);

        self::assertSame(2, $entity->num());
    }

    public function testCheckArraySizesIgnoresEmptyArraysWhenComparingSizes(): void
    {
        $entity = new EntityTestDouble([
            'arr' => ['a', 'b'],
            'arr2' => [],
        ], checkArraySizes: true);

        self::assertSame(2, $entity->num());
    }

    public function testCheckArraySizesThrowsForMismatchedArraySizesAndNamesTheOffendingKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/for arr2/');

        new EntityTestDouble([
            'arr' => ['a', 'b'],
            'arr2' => ['x', 'y', 'z'],
        ], checkArraySizes: true);
    }

    public function testSetUpdatesValueForKnownKey(): void
    {
        $entity = new EntityTestDouble();
        $entity->set('scalar', 'updated');

        self::assertSame('updated', $entity->get('scalar'));
    }

    public function testSetThrowsForUnknownKey(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\InvalidArgumentException::class);

        $entity->set('does_not_exist', 'value');
    }

    public function testSetThrowsWhenCanSetForbidsTheKey(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\InvalidArgumentException::class);

        $entity->set('readonly', 'value');
    }

    public function testSetThrowsWhenArrayPropertyIsGivenANonArrayValue(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\RuntimeException::class);

        $entity->set('arr', 'not-an-array');
    }

    public function testSetRejectsNullForArrayPropertyThatCannotBeNull(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\RuntimeException::class);

        $entity->set('arr', null);
    }

    public function testSetRecalculatesNumWhenArraySizeChanges(): void
    {
        $entity = new EntityTestDouble([
            'arr' => ['a'],
        ]);
        self::assertSame(1, $entity->num());

        $entity->set('arr', ['a', 'b', 'c']);

        self::assertSame(3, $entity->num());
    }

    public function testGetThrowsForUnknownKey(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\InvalidArgumentException::class);

        $entity->get('does_not_exist');
    }

    public function testHasReturnsFalseForDefaultEmptyValues(): void
    {
        $entity = new EntityTestDouble();

        self::assertFalse($entity->has('scalar'));
        self::assertFalse($entity->has('arr'));
        self::assertFalse($entity->has('nullable'));
    }

    public function testHasReturnsTrueForNonEmptyValues(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => 'value',
            'arr' => ['x'],
        ]);

        self::assertTrue($entity->has('scalar'));
        self::assertTrue($entity->has('arr'));
    }

    public function testHasThrowsForUnknownKey(): void
    {
        $entity = new EntityTestDouble();

        $this->expectException(\InvalidArgumentException::class);

        $entity->has('does_not_exist');
    }

    public function testClearResetsAllPropertiesToTheirEmptyDefaults(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => 'value',
            'arr' => ['a', 'b'],
        ]);
        $entity->clear();

        self::assertSame('', $entity->get('scalar'));
        self::assertSame([], $entity->get('arr'));
        self::assertSame(0, $entity->num());
    }

    public function testIsEmptyReturnsTrueForAllDefaultValues(): void
    {
        $entity = new EntityTestDouble();

        self::assertTrue($entity->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenAnyPropertyHasAValue(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => 'value',
        ]);

        self::assertFalse($entity->isEmpty());
    }

    public function testIsEmptyIgnoresPropertiesIdentifiedByIgnoreNonEmptyValue(): void
    {
        $entity = new IgnoringScalarEntityTestDouble([
            'scalar' => 'value',
        ]);

        self::assertTrue($entity->isEmpty());
    }

    public function testCompleteDataReturnsTheFullInternalDataArray(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => 'value',
            'arr' => ['a'],
        ]);

        self::assertSame([
            'scalar' => 'value',
            'arr' => ['a'],
            'arr2' => [],
            'nullable' => null,
            'readonly' => '',
        ], $entity->completeData());
    }

    public function testExplodeSplitsStringBySeparator(): void
    {
        self::assertSame(['a', 'b', 'c'], Entity::explode('a' . Entity::ARRAY_SEPARATOR . 'b' . Entity::ARRAY_SEPARATOR . 'c'));
    }

    public function testImplodeJoinsArrayWithSeparator(): void
    {
        self::assertSame('a' . Entity::ARRAY_SEPARATOR . 'b' . Entity::ARRAY_SEPARATOR . 'c', Entity::implode(['a', 'b', 'c']));
    }

    #[DataProvider('numericValueProvider')]
    public function testEnsureNumericValueCastsOrFallsBackToDefault(mixed $input, int|float $default, mixed $expected): void
    {
        $entity = new EntityTestDouble([
            'scalar' => $input,
        ]);
        $entity->callEnsureNumericValue('scalar', $default);

        self::assertSame($expected, $entity->get('scalar'));
    }

    /**
     * @return array<string, string[]|int[]|float[]|bool[]>
     */
    public static function numericValueProvider(): array
    {
        return [
            'numeric string is cast to float' => ['42', 0, 42.0],
            'bool true is cast to int 1' => [true, 0, 1],
            'bool false is cast to int 0' => [false, 5, 0],
            'non-numeric string falls back to default' => ['abc', 7, 7],
        ];
    }

    public function testEnsureNumericValueSilentlyIgnoresUnknownKeys(): void
    {
        $entity = new EntityTestDouble();

        // Must not throw, even though "unknown" is not a declared property.
        $entity->callEnsureNumericValue('unknown', 0);

        self::assertTrue(true);
    }

    public function testEnsureNullIfEmptyConvertsEmptyStringToNull(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => '',
        ]);
        $entity->callEnsureNullIfEmpty('scalar');

        self::assertNull($entity->get('scalar'));
    }

    public function testEnsureNullIfEmptyConvertsEmptyArrayToNull(): void
    {
        $entity = new EntityTestDouble();
        $entity->callEnsureNullIfEmpty('arr');

        self::assertNull($entity->get('arr'));
    }

    public function testEnsureNullIfEmptyTreatsZeroAsNullOnlyWhenRequested(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => '0',
        ]);
        $entity->callEnsureNullIfEmpty('scalar', treatZeroAsNull: true);

        self::assertNull($entity->get('scalar'));
    }

    public function testEnsureNullIfEmptyCastsNonEmptyValueToFloatWhenRequested(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => '3',
        ]);
        $entity->callEnsureNullIfEmpty('scalar', ensureNumericIfNotEmpty: true);

        self::assertSame(3.0, $entity->get('scalar'));
    }

    public function testEnsureNullIfEmptyLeavesZeroAsNumericWhenTreatZeroAsNullIsFalse(): void
    {
        $entity = new EntityTestDouble([
            'scalar' => '0',
        ]);
        $entity->callEnsureNullIfEmpty('scalar', ensureNumericIfNotEmpty: true, treatZeroAsNull: false);

        self::assertSame(0.0, $entity->get('scalar'));
    }
}
