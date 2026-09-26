<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Parameter\Application\ActivityCreationMode;
use Runalyze\Parameter\Application\DatabaseOrder;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityFormTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityForm $object;

    protected function setUp(): void
    {
        $this->object = new ActivityForm();
    }

    #[DataProvider('legendsDefaultingToTrueProvider')]
    public function testLegendDefaultsToVisible(string $key): void
    {
        self::assertTrue($this->object->show($key));
    }

    /**
     * @return array<string, string[]>
     */
    public static function legendsDefaultingToTrueProvider(): array
    {
        return [
            'SPORT' => ['SPORT'],
            'GENERAL' => ['GENERAL'],
            'DISTANCE' => ['DISTANCE'],
            'SPLITS' => ['SPLITS'],
            'WEATHER' => ['WEATHER'],
            'OTHER' => ['OTHER'],
        ];
    }

    #[DataProvider('legendsDefaultingToFalseProvider')]
    public function testLegendDefaultsToHidden(string $key): void
    {
        self::assertFalse($this->object->show($key));
    }

    /**
     * @return array<string, string[]>
     */
    public static function legendsDefaultingToFalseProvider(): array
    {
        return [
            'NOTES' => ['NOTES'],
            'PUBLIC' => ['PUBLIC'],
            'ELEVATION' => ['ELEVATION'],
            'GPS' => ['GPS'],
        ];
    }

    public function testUpdateChangesLegendVisibility(): void
    {
        $this->object->update('NOTES', 'true');

        self::assertTrue($this->object->show('NOTES'));
    }

    public function testUpdateCanHideAnInitiallyVisibleLegend(): void
    {
        $this->object->update('SPORT', 'false');

        self::assertFalse($this->object->show('SPORT'));
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
        self::assertInstanceOf(ActivityCreationMode::class, $this->object->creationMode());
        self::assertSame(ActivityCreationMode::UPLOAD, $this->object->creationMode()->value());
        self::assertTrue($this->object->creationMode()->usesUpload());
    }

    public function testCorrectElevationDefaultsToTrue(): void
    {
        self::assertTrue($this->object->correctElevation());
    }

    public function testLoadWeatherDefaultsToTrue(): void
    {
        self::assertTrue($this->object->loadWeather());
    }

    public function testWeatherLocationDefaultsToEmptyString(): void
    {
        self::assertSame('', $this->object->weatherLocation());
    }

    public function testComputeCaloriesDefaultsToTrue(): void
    {
        self::assertTrue($this->object->computeCalories());
    }

    public function testDetectPausesDefaultsToTrue(): void
    {
        self::assertTrue($this->object->detectPauses());
    }

    public function testOrderSportsReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(DatabaseOrder::class, $this->object->orderSports());
        self::assertSame(DatabaseOrder::ASC, $this->object->orderSports()->value());
    }

    public function testOrderTypesReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(DatabaseOrder::class, $this->object->orderTypes());
        self::assertSame(DatabaseOrder::ASC, $this->object->orderTypes()->value());
    }

    public function testOrderEquipmentReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(DatabaseOrder::class, $this->object->orderEquipment());
        self::assertSame(DatabaseOrder::ASC, $this->object->orderEquipment()->value());
    }

    public function testIgnoredActivityIDsDefaultsToEmptyArray(): void
    {
        self::assertSame([], $this->object->ignoredActivityIDs());
    }

    public function testIgnoreActivityIDAppendsToTheList(): void
    {
        $this->object->ignoreActivityID('12345');

        self::assertSame(['12345'], $this->object->ignoredActivityIDs());
    }

    public function testIgnoreActivityIDCanBeCalledMultipleTimesPreservingOrder(): void
    {
        $this->object->ignoreActivityID('111');
        $this->object->ignoreActivityID('222');
        $this->object->ignoreActivityID('333');

        self::assertSame(['111', '222', '333'], $this->object->ignoredActivityIDs());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        self::assertSame([
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
