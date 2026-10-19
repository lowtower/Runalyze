<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class VO2maxTest extends \PHPUnit\Framework\TestCase
{
    protected VO2max $object;

    protected function setUp(): void
    {
        $this->object = new VO2max();
    }

    public function testDaysDefaultsToThirty(): void
    {
        $this->assertSame(30, $this->object->days());
    }

    public function testDaysCanBeChanged(): void
    {
        $this->object->setValues([
            'VO2MAX_DAYS' => '14',
        ]);

        $this->assertSame(14, $this->object->days());
    }

    public function testManualFactorDefaultsToNull(): void
    {
        $this->assertNull($this->object->manualFactor());
        $this->assertFalse($this->object->useManualFactor());
    }

    public function testManualFactorCanBeSetWithinBounds(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '1.5',
        ]);

        $this->assertEqualsWithDelta(1.5, $this->object->manualFactor(), PHP_FLOAT_EPSILON);
        $this->assertTrue($this->object->useManualFactor());
    }

    public function testManualFactorCanBeResetToNullViaEmptyString(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '1.5',
        ]);
        $this->assertTrue($this->object->useManualFactor());

        $this->object->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '',
        ]);

        $this->assertNull($this->object->manualFactor());
        $this->assertFalse($this->object->useManualFactor());
    }

    public function testManualFactorClampsBelowConfiguredMinimum(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '0.1',
        ]);

        $this->assertEqualsWithDelta(0.50, $this->object->manualFactor(), PHP_FLOAT_EPSILON);
        $this->assertTrue($this->object->useManualFactor());
    }

    public function testManualFactorClampsAboveConfiguredMaximum(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_CORRECTOR' => '3.0',
        ]);

        $this->assertEqualsWithDelta(2.00, $this->object->manualFactor(), PHP_FLOAT_EPSILON);
        $this->assertTrue($this->object->useManualFactor());
    }

    public function testManualValueDefaultsToZero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->object->manualValue(), PHP_FLOAT_EPSILON);
        $this->assertFalse($this->object->useManualValue());
    }

    public function testManualValueAcceptsCommaAsDecimalSeparator(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_VALUE' => '55,5',
        ]);

        $this->assertEqualsWithDelta(55.5, $this->object->manualValue(), PHP_FLOAT_EPSILON);
        $this->assertTrue($this->object->useManualValue());
    }

    public function testManualValueAcceptsPointAsDecimalSeparator(): void
    {
        $this->object->setValues([
            'VO2MAX_MANUAL_VALUE' => '42.0',
        ]);

        $this->assertEqualsWithDelta(42.0, $this->object->manualValue(), PHP_FLOAT_EPSILON);
        $this->assertTrue($this->object->useManualValue());
    }

    public function testUseElevationCorrectionDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->useElevationCorrection());
    }

    public function testUseElevationCorrectionCanBeEnabled(): void
    {
        $this->object->setValues([
            'VO2MAX_USE_CORRECTION_FOR_ELEVATION' => 'true',
        ]);

        $this->assertTrue($this->object->useElevationCorrection());
    }

    public function testCorrectionForPositiveElevationDefaultsToTwo(): void
    {
        $this->assertSame(2, $this->object->correctionForPositiveElevation());
    }

    public function testCorrectionForNegativeElevationDefaultsToMinusOne(): void
    {
        $this->assertSame(-1, $this->object->correctionForNegativeElevation());
    }

    public function testCorrectionValuesCanBeChanged(): void
    {
        $this->object->setValues([
            'VO2MAX_CORRECTION_POSITIVE_ELEVATION' => '5',
            'VO2MAX_CORRECTION_NEGATIVE_ELEVATION' => '-3',
        ]);

        $this->assertSame(5, $this->object->correctionForPositiveElevation());
        $this->assertSame(-3, $this->object->correctionForNegativeElevation());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        $this->assertSame([
            'VO2MAX_DAYS',
            'VO2MAX_MANUAL_CORRECTOR',
            'VO2MAX_MANUAL_VALUE',
            'VO2MAX_USE_CORRECTION_FOR_ELEVATION',
            'VO2MAX_CORRECTION_POSITIVE_ELEVATION',
            'VO2MAX_CORRECTION_NEGATIVE_ELEVATION',
        ], $this->object->keys());
    }

    public function testUpdateFromPostForManualValueOnlySetsReloadFlagWithoutError(): void
    {
        $backup = $_POST;
        $_POST = [
            'VO2MAX_MANUAL_VALUE' => '60',
        ];

        $this->object->updateFromPost();

        $this->assertEqualsWithDelta(60.0, $this->object->manualValue(), PHP_FLOAT_EPSILON);

        $_POST = $backup;
    }
}
