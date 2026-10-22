<?php

declare(strict_types=1);

namespace Runalyze\Common\Enum;

use PHPUnit\Framework\TestCase;

final class FactoryTraitEnumFixture extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const string ALPHA = 'alpha';

    public const string BETA_GAMMA = 'beta_gamma';
}

final class SecondFactoryTraitEnumFixture extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const string GAMMA = 'gamma';
}

final class EnumWithoutGetEnumFixture
{
    use AbstractEnumFactoryTrait;
}

final class Alpha
{
}

final class BetaGamma
{
}

final class Gamma
{
}

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AbstractEnumFactoryTraitTest extends TestCase
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
        $this->expectExceptionMessageIsOrContains('Invalid enum "does_not_exist".');

        FactoryTraitEnumFixture::get('does_not_exist');
    }

    public function testStaticCachingDoesNotLeakBetweenDifferentClassesUsingTheTrait(): void
    {
        FactoryTraitEnumFixture::get('alpha');
        SecondFactoryTraitEnumFixture::get('gamma');

        $firstCache = $this->readClassNamesCacheOf(FactoryTraitEnumFixture::class);
        $secondCache = $this->readClassNamesCacheOf(SecondFactoryTraitEnumFixture::class);

        $this->assertSame([
            'alpha' => 'Alpha',
            'beta_gamma' => 'BetaGamma',
        ], $firstCache);
        $this->assertSame([
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
        $this->expectExceptionMessageIsOrContains('Classes using this trait must have static method getEnum().');

        EnumWithoutGetEnumFixture::get('anything');
    }
}
