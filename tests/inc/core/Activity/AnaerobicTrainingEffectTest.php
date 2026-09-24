<?php

namespace Runalyze\Activity;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AnaerobicTrainingEffectTest extends \PHPUnit\Framework\TestCase
{
    public function testLowerBoundary(): void
    {
        $effect = new AnaerobicTrainingEffect(0.0);

        $this->assertSame(0.0, $effect->value());
    }

    public function testUpperBoundary(): void
    {
        $effect = new AnaerobicTrainingEffect(5.0);

        $this->assertSame(5.0, $effect->value());
    }

    public function testInvalidValueTooSmall(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect(-0.1);
    }

    public function testInvalidValueTooBig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect(5.1);
    }

    public function testInvalidValueTooSmallAsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect('-0.1');
    }

    public function testInvalidValueTooBigAsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect('5.1');
    }

    public function testInvalidValueNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect(false);
    }

    public function testBooleanTrueIsRejectedWithoutBeingCoercedToOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect(true);
    }

    public function testInvalidValueString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect('foobar');
    }

    public function testValidValueZero(): void
    {
        $effect = new AnaerobicTrainingEffect(0.0);

        $this->assertSame(0.0, $effect->value());
    }

    public function testValidCommaValue(): void
    {
        $effect = new AnaerobicTrainingEffect('3,5');

        $this->assertSame(3.5, $effect->value());
    }

    public function testValidCommaValueWithSurroundingWhitespace(): void
    {
        $effect = new AnaerobicTrainingEffect(' 3,25 ');

        $this->assertSame(3.25, $effect->value());
    }

    public function testIntegerValueIsAcceptedAsFloat(): void
    {
        $effect = new AnaerobicTrainingEffect(3);

        $this->assertSame(3.0, $effect->value());
    }

    public function testNanValueIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AnaerobicTrainingEffect(NAN);
    }

    public function testSimpleValue(): void
    {
        $Effect = new AnaerobicTrainingEffect(3.1);

        $this->assertTrue($Effect->isKnown());
        $this->assertEquals(3.1, $Effect->value());
        $this->assertEquals(TrainingEffectLevel::IMPROVING, $Effect->level());

        $this->assertEquals(2.9, $Effect->set(2.9)->value());
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, $Effect->level());

        $this->assertFalse($Effect->set(null)->isKnown());
    }

    public function testSetReturnsSelfForFluentChaining(): void
    {
        $effect = new AnaerobicTrainingEffect();

        $this->assertSame($effect, $effect->set(1.5));
    }

    public function testFormattingValues(): void
    {
        $this->assertEquals('', AnaerobicTrainingEffect::format(null));
        $this->assertEquals('3.1', AnaerobicTrainingEffect::format(3.14));
        $this->assertEquals('5.0', AnaerobicTrainingEffect::format(5.0));
    }

    public function testUnknownValue(): void
    {
        $Effect = new AnaerobicTrainingEffect();

        $this->assertFalse($Effect->isKnown());
        $this->assertEquals('', $Effect->string());
        $this->assertEquals('', $Effect->shortDescription());
        $this->assertEquals('', $Effect->description());
        $this->assertEquals(0, $Effect->numericValue());
        $this->assertNull($Effect->value());
        $this->assertNull($Effect->level());
    }

    public function testNumericValueReturnsStoredValueWhenKnown(): void
    {
        $effect = new AnaerobicTrainingEffect(2.7);

        $this->assertSame(2.7, $effect->numericValue());
    }

    public function testLevelDelegatesToTrainingEffectLevel(): void
    {
        $effect = new AnaerobicTrainingEffect(3.1);

        $this->assertSame(TrainingEffectLevel::levelFor(3.1), $effect->level());
    }

    public function testShortDescriptionDelegatesToTrainingEffectLevel(): void
    {
        $effect = new AnaerobicTrainingEffect(3.1);

        $this->assertSame(TrainingEffectLevel::label($effect->level()), $effect->shortDescription());
    }

    public function testDescriptionDelegatesToTrainingEffectLevel(): void
    {
        $effect = new AnaerobicTrainingEffect(3.1);

        $this->assertSame(TrainingEffectLevel::description($effect->level()), $effect->description());
    }
}
