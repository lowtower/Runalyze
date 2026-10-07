<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

use Runalyze\Configuration;

#[\PHPUnit\Framework\Attributes\Group('default')]
class DataTest extends \PHPUnit\Framework\TestCase
{
    protected Data $object;

    protected function setUp(): void
    {
        $this->resetConfigurationSingleton();

        $this->object = new Data();
    }

    protected function tearDown(): void
    {
        $this->resetConfigurationSingleton();
    }

    /**
     * Configuration is a static service locator/singleton (Categories cache,
     * AccountID, ValuesFromDB). Since phpunit.dist.xml runs with
     * processIsolation="false", all tests share the same PHP process, so its
     * static state could otherwise leak between test files/methods. This
     * resets it to a clean, DB-free state (both AccountID and ValuesFromDB
     * stay null, so Configuration::get() never triggers Category::setUserID()'s
     * DB-loading branch).
     */
    private function resetConfigurationSingleton(): void
    {
        new \ReflectionProperty(Configuration::class, 'Categories')->setValue(null, []);
        new \ReflectionProperty(Configuration::class, 'AccountID')->setValue(null, null);
        new \ReflectionProperty(Configuration::class, 'ValuesFromDB')->setValue(null, null);
    }

    public function testStartTimeDefaultsToZero(): void
    {
        self::assertSame(0, $this->object->startTime());
    }

    public function testUpdateStartTimeChangesStoredValue(): void
    {
        $this->object->updateStartTime(1_700_000_000);

        self::assertSame(1_700_000_000, $this->object->startTime());
    }

    public function testUpdateStartTimeRejectsNullWithTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $this->object->updateStartTime(null);
    }

    public function testHRmaxDefaultsTo200(): void
    {
        self::assertSame(200, $this->object->HRmax());
    }

    public function testUpdateHRmaxChangesStoredValue(): void
    {
        $this->object->updateHRmax(185);

        self::assertSame(185, $this->object->HRmax());
    }

    public function testHRrestDefaultsTo60(): void
    {
        self::assertSame(60, $this->object->HRrest());
    }

    public function testUpdateHRrestChangesStoredValue(): void
    {
        $this->object->updateHRrest(48);

        self::assertSame(48, $this->object->HRrest());
    }

    public function testVo2maxShapeDefaultsToZero(): void
    {
        self::assertSame(0.0, $this->object->vo2maxShape());
    }

    public function testUpdateVo2maxShapeChangesStoredValue(): void
    {
        $this->object->updateVO2maxShape(52.3);

        self::assertSame(52.3, $this->object->vo2maxShape());
    }

    public function testVo2maxReturnsShapeValueWhenNoManualValueIsConfigured(): void
    {
        $this->object->updateVO2maxShape(48.0);

        self::assertFalse(Configuration::VO2max()->useManualValue());
        self::assertSame(48.0, $this->object->vo2max());
    }

    public function testVo2maxReturnsManualValueFromVO2maxCategoryWhenConfigured(): void
    {
        $this->object->updateVO2maxShape(48.0);
        Configuration::VO2max()->setValues([
            'VO2MAX_MANUAL_VALUE' => '55.5',
        ]);

        self::assertTrue(Configuration::VO2max()->useManualValue());
        self::assertSame(55.5, $this->object->vo2max());
    }

    public function testVo2maxCorrectorDefaultsToOne(): void
    {
        self::assertSame(1.0, $this->object->vo2maxCorrector());
    }

    public function testUpdateVo2maxCorrectorChangesStoredValue(): void
    {
        $this->object->updateVO2maxCorrector(0.95);

        self::assertSame(0.95, $this->object->vo2maxCorrector());
    }

    public function testVo2maxCorrectionFactorReturnsCorrectorValueWhenNoManualFactorIsConfigured(): void
    {
        $this->object->updateVO2maxCorrector(0.92);

        self::assertFalse(Configuration::VO2max()->useManualFactor());
        self::assertSame(0.92, $this->object->vo2maxCorrectionFactor());
    }

    public function testVo2maxCorrectionFactorReturnsManualFactorFromVO2maxCategoryWhenConfigured(): void
    {
        $this->object->updateVO2maxCorrector(0.92);
        Configuration::VO2max()->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '1.5',
        ]);

        self::assertTrue(Configuration::VO2max()->useManualFactor());
        self::assertSame(1.5, $this->object->vo2maxCorrectionFactor());
    }

    public function testBasicEnduranceDefaultsToZero(): void
    {
        self::assertSame(0, $this->object->basicEndurance());
    }

    public function testUpdateBasicEnduranceChangesStoredValue(): void
    {
        $this->object->updateBasicEndurance(75);

        self::assertSame(75, $this->object->basicEndurance());
    }

    public function testMaxATLDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        self::assertSame(1, $this->object->maxATL());
    }

    public function testUpdateMaxATLAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxATL(42);

        self::assertSame(42, $this->object->maxATL());
    }

    public function testUpdateMaxATLClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxATL(-5);

        self::assertSame(1, $this->object->maxATL());
    }

    public function testMaxCTLDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        self::assertSame(1, $this->object->maxCTL());
    }

    public function testUpdateMaxCTLAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxCTL(37);

        self::assertSame(37, $this->object->maxCTL());
    }

    public function testUpdateMaxCTLClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxCTL(0);

        self::assertSame(1, $this->object->maxCTL());
    }

    public function testMaxTrimpDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        self::assertSame(1, $this->object->maxTrimp());
    }

    public function testUpdateMaxTrimpAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxTrimp(310);

        self::assertSame(310, $this->object->maxTrimp());
    }

    public function testUpdateMaxTrimpClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxTrimp(-1);

        self::assertSame(1, $this->object->maxTrimp());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        self::assertSame([
            'START_TIME',
            'HF_MAX',
            'HF_REST',
            'VO2MAX_FORM',
            'VO2MAX_CORRECTOR',
            'BASIC_ENDURANCE',
            'MAX_ATL',
            'MAX_CTL',
            'MAX_TRIMP',
        ], $this->object->keys());
    }

    public function testDefaultFieldsetImplementationReturnsNull(): void
    {
        self::assertNull($this->object->Fieldset());
    }
}
