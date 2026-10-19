<?php

namespace Runalyze\Common\Enum;

use PHPUnit\Framework\TestCase;

class SampleEnumFixture extends AbstractEnum
{
    public const SUNDAY = 0;

    public const MONDAY = 1;

    public const TUESDAY = 2;
}

class AnotherSampleEnumFixture extends AbstractEnum
{
    public const RED = 10;

    public const GREEN = 20;
}

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AbstractEnumTest extends TestCase
{
    public function testGetEnumReturnsAllConstantsAsNameValueMap(): void
    {
        $this->assertSame(
            [
                'SUNDAY' => 0,
                'MONDAY' => 1,
                'TUESDAY' => 2,
            ],
            SampleEnumFixture::getEnum()
        );
    }

    public function testGetEnumDoesNotMixConstantsBetweenDifferentSubclasses(): void
    {
        $sample = SampleEnumFixture::getEnum();
        $another = AnotherSampleEnumFixture::getEnum();

        $this->assertSame([
            'SUNDAY' => 0,
            'MONDAY' => 1,
            'TUESDAY' => 2,
        ], $sample);
        $this->assertSame([
            'RED' => 10,
            'GREEN' => 20,
        ], $another);
    }

    public function testGetEnumPopulatesInternalCacheAfterFirstCall(): void
    {
        $reflection = new \ReflectionClass(AbstractEnum::class);
        $cacheProperty = $reflection->getProperty('ConstantsCache');

        // Force a fresh, known cache state for this test.
        $cacheProperty->setValue(null, []);
        $this->assertArrayNotHasKey(SampleEnumFixture::class, $cacheProperty->getValue());

        SampleEnumFixture::getEnum();

        $this->assertArrayHasKey(SampleEnumFixture::class, $cacheProperty->getValue());
    }

    public function testIsValidNameIsCaseInsensitiveByDefault(): void
    {
        $this->assertTrue(SampleEnumFixture::isValidName('monday'));
        $this->assertTrue(SampleEnumFixture::isValidName('Monday'));
        $this->assertTrue(SampleEnumFixture::isValidName('MONDAY'));
    }

    public function testIsValidNameInStrictModeRequiresExactCase(): void
    {
        $this->assertFalse(SampleEnumFixture::isValidName('Monday', true));
        $this->assertTrue(SampleEnumFixture::isValidName('MONDAY', true));
    }

    public function testIsValidNameReturnsFalseForUnknownName(): void
    {
        $this->assertFalse(SampleEnumFixture::isValidName('FRIDAY'));
    }

    public function testIsValidValueReturnsTrueForKnownValue(): void
    {
        $this->assertTrue(SampleEnumFixture::isValidValue(1));
    }

    public function testIsValidValueReturnsFalseForUnknownValue(): void
    {
        $this->assertFalse(SampleEnumFixture::isValidValue(123));
    }

    public function testIsValidValueDefaultsToStrictTypeComparison(): void
    {
        $this->assertFalse(SampleEnumFixture::isValidValue('1'));
        $this->assertTrue(SampleEnumFixture::isValidValue('1', false));
    }
}
