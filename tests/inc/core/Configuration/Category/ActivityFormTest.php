<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Parameter\Application\ActivityCreationMode;
use Runalyze\Parameter\Application\DatabaseOrder;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityFormTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityForm $object;

    protected function setUp(): void
    {
        $this->object = new ActivityForm();
    }

    #[DataProvider('legendsDefaultingToTrueProvider')]
    public function testLegendDefaultsToVisible(string $key): void
    {
        $this->assertTrue($this->object->show($key));
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function legendsDefaultingToTrueProvider(): \Iterator
    {
        yield 'SPORT' => ['SPORT'];
        yield 'GENERAL' => ['GENERAL'];
        yield 'DISTANCE' => ['DISTANCE'];
        yield 'SPLITS' => ['SPLITS'];
        yield 'WEATHER' => ['WEATHER'];
        yield 'OTHER' => ['OTHER'];
    }

    #[DataProvider('legendsDefaultingToFalseProvider')]
    public function testLegendDefaultsToHidden(string $key): void
    {
        $this->assertFalse($this->object->show($key));
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function legendsDefaultingToFalseProvider(): \Iterator
    {
        yield 'NOTES' => ['NOTES'];
        yield 'PUBLIC' => ['PUBLIC'];
        yield 'ELEVATION' => ['ELEVATION'];
        yield 'GPS' => ['GPS'];
    }

    public function testUpdateChangesLegendVisibility(): void
    {
        $this->object->update('NOTES', 'true');

        $this->assertTrue($this->object->show('NOTES'));
    }

    public function testUpdateCanHideAnInitiallyVisibleLegend(): void
    {
        $this->object->update('SPORT', 'false');

        $this->assertFalse($this->object->show('SPORT'));
    }

    public function testShowThrowsExceptionForUnknownLegendKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->object->show('DOES_NOT_EXIST');
    }

    public function testUpdateThrowsExceptionForUnknownLegendKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->object->update('DOES_NOT_EXIST', 'true');
    }

    public function testCreationModeReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ActivityCreationMode::class, $this->object->creationMode());
        $this->assertSame(ActivityCreationMode::UPLOAD, $this->object->creationMode()->value());
        $this->assertTrue($this->object->creationMode()->usesUpload());
    }

    public function testCorrectElevationDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->correctElevation());
    }

    public function testLoadWeatherDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->loadWeather());
    }

    public function testWeatherLocationDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->object->weatherLocation());
    }

    public function testComputeCaloriesDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->computeCalories());
    }

    public function testDetectPausesDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->detectPauses());
    }

    public function testOrderSportsReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(DatabaseOrder::class, $this->object->orderSports());
        $this->assertSame(DatabaseOrder::ASC, $this->object->orderSports()->value());
    }

    public function testOrderTypesReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(DatabaseOrder::class, $this->object->orderTypes());
        $this->assertSame(DatabaseOrder::ASC, $this->object->orderTypes()->value());
    }

    public function testOrderEquipmentReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(DatabaseOrder::class, $this->object->orderEquipment());
        $this->assertSame(DatabaseOrder::ASC, $this->object->orderEquipment()->value());
    }

    public function testIgnoredActivityIDsDefaultsToEmptyArray(): void
    {
        $this->assertSame([], $this->object->ignoredActivityIDs());
    }

    public function testIgnoreActivityIDAppendsToTheList(): void
    {
        $this->object->ignoreActivityID('12345');

        $this->assertSame(['12345'], $this->object->ignoredActivityIDs());
    }

    public function testIgnoreActivityIDCanBeCalledMultipleTimesPreservingOrder(): void
    {
        $this->object->ignoreActivityID('111');
        $this->object->ignoreActivityID('222');
        $this->object->ignoreActivityID('333');

        $this->assertSame(['111', '222', '333'], $this->object->ignoredActivityIDs());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        $this->assertSame([
            'FORMULAR_SHOW_SPORT',
            'FORMULAR_SHOW_GENERAL',
            'FORMULAR_SHOW_DISTANCE',
            'FORMULAR_SHOW_SPLITS',
            'FORMULAR_SHOW_WEATHER',
            'FORMULAR_SHOW_OTHER',
            'FORMULAR_SHOW_NOTES',
            'FORMULAR_SHOW_PUBLIC',
            'FORMULAR_SHOW_ELEVATION',
            'FORMULAR_SHOW_GPS',
            'TRAINING_CREATE_MODE',
            'TRAINING_DO_ELEVATION',
            'TRAINING_LOAD_WEATHER',
            'PLZ',
            'COMPUTE_KCAL',
            'TRAINING_SORT_SPORTS',
            'TRAINING_SORT_TYPES',
            'TRAINING_SORT_SHOES',
            'GARMIN_IGNORE_IDS',
            'DETECT_PAUSES',
            'IMPORT_MAX_HR_USER',
        ], $this->object->keys());
    }
}
