<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

use Runalyze\Configuration;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class DataTest extends \PHPUnit\Framework\TestCase
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
        $this->assertSame(0, $this->object->startTime());
    }

    public function testUpdateStartTimeChangesStoredValue(): void
    {
        $this->object->updateStartTime(1_700_000_000);

        $this->assertSame(1_700_000_000, $this->object->startTime());
    }

    public function testUpdateStartTimeRejectsNullWithTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $this->object->updateStartTime(null);
    }

    public function testHRmaxDefaultsTo200(): void
    {
        $this->assertSame(200, $this->object->HRmax());
    }

    public function testUpdateHRmaxChangesStoredValue(): void
    {
        $this->object->updateHRmax(185);

        $this->assertSame(185, $this->object->HRmax());
    }

    public function testHRrestDefaultsTo60(): void
    {
        $this->assertSame(60, $this->object->HRrest());
    }

    public function testUpdateHRrestChangesStoredValue(): void
    {
        $this->object->updateHRrest(48);

        $this->assertSame(48, $this->object->HRrest());
    }

    public function testVo2maxShapeDefaultsToZero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->object->vo2maxShape(), PHP_FLOAT_EPSILON);
    }

    public function testUpdateVo2maxShapeChangesStoredValue(): void
    {
        $this->object->updateVO2maxShape(52.3);

        $this->assertEqualsWithDelta(52.3, $this->object->vo2maxShape(), PHP_FLOAT_EPSILON);
    }

    public function testVo2maxReturnsShapeValueWhenNoManualValueIsConfigured(): void
    {
        $this->object->updateVO2maxShape(48.0);

        $this->assertFalse(Configuration::VO2max()->useManualValue());
        $this->assertEqualsWithDelta(48.0, $this->object->vo2max(), PHP_FLOAT_EPSILON);
    }

    public function testVo2maxReturnsManualValueFromVO2maxCategoryWhenConfigured(): void
    {
        $this->object->updateVO2maxShape(48.0);
        Configuration::VO2max()->setValues([
            'VO2MAX_MANUAL_VALUE' => '55.5',
        ]);

        $this->assertTrue(Configuration::VO2max()->useManualValue());
        $this->assertEqualsWithDelta(55.5, $this->object->vo2max(), PHP_FLOAT_EPSILON);
    }

    public function testVo2maxCorrectorDefaultsToOne(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->object->vo2maxCorrector(), PHP_FLOAT_EPSILON);
    }

    public function testUpdateVo2maxCorrectorChangesStoredValue(): void
    {
        $this->object->updateVO2maxCorrector(0.95);

        $this->assertEqualsWithDelta(0.95, $this->object->vo2maxCorrector(), PHP_FLOAT_EPSILON);
    }

    public function testVo2maxCorrectionFactorReturnsCorrectorValueWhenNoManualFactorIsConfigured(): void
    {
        $this->object->updateVO2maxCorrector(0.92);

        $this->assertFalse(Configuration::VO2max()->useManualFactor());
        $this->assertEqualsWithDelta(0.92, $this->object->vo2maxCorrectionFactor(), PHP_FLOAT_EPSILON);
    }

    public function testVo2maxCorrectionFactorReturnsManualFactorFromVO2maxCategoryWhenConfigured(): void
    {
        $this->object->updateVO2maxCorrector(0.92);
        Configuration::VO2max()->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '1.5',
        ]);

        $this->assertTrue(Configuration::VO2max()->useManualFactor());
        $this->assertEqualsWithDelta(1.5, $this->object->vo2maxCorrectionFactor(), PHP_FLOAT_EPSILON);
    }

    public function testBasicEnduranceDefaultsToZero(): void
    {
        $this->assertSame(0, $this->object->basicEndurance());
    }

    public function testUpdateBasicEnduranceChangesStoredValue(): void
    {
        $this->object->updateBasicEndurance(75);

        $this->assertSame(75, $this->object->basicEndurance());
    }

    public function testMaxATLDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        $this->assertSame(1, $this->object->maxATL());
    }

    public function testUpdateMaxATLAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxATL(42);

        $this->assertSame(42, $this->object->maxATL());
    }

    public function testUpdateMaxATLClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxATL(-5);

        $this->assertSame(1, $this->object->maxATL());
    }

    public function testMaxCTLDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        $this->assertSame(1, $this->object->maxCTL());
    }

    public function testUpdateMaxCTLAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxCTL(37);

        $this->assertSame(37, $this->object->maxCTL());
    }

    public function testUpdateMaxCTLClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxCTL(0);

        $this->assertSame(1, $this->object->maxCTL());
    }

    public function testMaxTrimpDefaultsToOneEvenThoughStoredValueIsZero(): void
    {
        $this->assertSame(1, $this->object->maxTrimp());
    }

    public function testUpdateMaxTrimpAllowsValuesAboveOne(): void
    {
        $this->object->updateMaxTrimp(310);

        $this->assertSame(310, $this->object->maxTrimp());
    }

    public function testUpdateMaxTrimpClampsBackToOneForNonPositiveValues(): void
    {
        $this->object->updateMaxTrimp(-1);

        $this->assertSame(1, $this->object->maxTrimp());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        $this->assertSame([
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
        $this->assertNull($this->object->Fieldset());
    }
}
