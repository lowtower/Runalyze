<?php

declare(strict_types=1);

namespace Runalyze\Common\Enum;

use PHPUnit\Framework\TestCase;

class FactoryTraitEnumFixture extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const ALPHA = 'alpha';

    public const BETA_GAMMA = 'beta_gamma';
}

class SecondFactoryTraitEnumFixture extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const GAMMA = 'gamma';
}

class EnumWithoutGetEnumFixture
{
    use AbstractEnumFactoryTrait;
}

class Alpha
{
}

class BetaGamma
{
}

class Gamma
{
}

#[\PHPUnit\Framework\Attributes\Group('default')]
class AbstractEnumFactoryTraitTest extends TestCase
{
    public function testGetReturnsInstanceOfClassMatchingSingleWordConstant(): void
    {
        $result = FactoryTraitEnumFixture::get('alpha');

        $this->assertInstanceOf(Alpha::class, $result);
    }

    public function testGetReturnsInstanceOfClassMatchingMultiWordConstant(): void
    {
        $result = FactoryTraitEnumFixture::get('beta_gamma');

        $this->assertInstanceOf(BetaGamma::class, $result);
    }

    public function testGetThrowsInvalidArgumentExceptionForUnknownEnumValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid enum "does_not_exist".');

        FactoryTraitEnumFixture::get('does_not_exist');
    }

    public function testStaticCachingDoesNotLeakBetweenDifferentClassesUsingTheTrait(): void
    {
        FactoryTraitEnumFixture::get('alpha');
        SecondFactoryTraitEnumFixture::get('gamma');

        $firstCache = $this->readClassNamesCacheOf(FactoryTraitEnumFixture::class);
        $secondCache = $this->readClassNamesCacheOf(SecondFactoryTraitEnumFixture::class);

        self::assertSame([
            'alpha' => 'Alpha',
            'beta_gamma' => 'BetaGamma',
        ], $firstCache);
        self::assertSame([
            'gamma' => 'Gamma',
        ], $secondCache);
    }

    private function readClassNamesCacheOf(string $class): ?array
    {
        $property = new \ReflectionProperty($class, 'ClassNames');

        return $property->getValue();
    }

    public function testGetThrowsBadMethodCallExceptionWhenGetEnumMethodIsMissing(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Classes using this trait must have static method getEnum().');

        EnumWithoutGetEnumFixture::get('anything');
    }
}
