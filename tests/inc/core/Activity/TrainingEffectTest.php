<?php

declare(strict_types=1);

namespace Runalyze\Activity;

final class TrainingEffectTest extends \PHPUnit\Framework\TestCase
{
    public function testLowerBoundary(): void
    {
        $effect = new TrainingEffect(0.0);

        $this->assertEqualsWithDelta(0.0, $effect->value(), PHP_FLOAT_EPSILON);
    }

    public function testUpperBoundary(): void
    {
        $effect = new TrainingEffect(5.0);

        $this->assertEqualsWithDelta(5.0, $effect->value(), PHP_FLOAT_EPSILON);
    }

    public function testInvalidValueTooSmall(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrainingEffect(-0.1);
    }

    public function testInvalidValueTooBig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrainingEffect(5.1);
    }

    public function testInvalidValueNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrainingEffect(false);
    }

    public function testInvalidValueString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrainingEffect('foobar');
    }

    public function testValidValueZero(): void
    {
        $effect = new TrainingEffect(0.0);

        $this->assertEqualsWithDelta(0.0, $effect->value(), PHP_FLOAT_EPSILON);
    }

    public function testValidCommaValue(): void
    {
        $effect = new TrainingEffect('3,5');

        $this->assertEqualsWithDelta(3.5, $effect->value(), PHP_FLOAT_EPSILON);
    }

    public function testSimpleValue(): void
    {
        $Effect = new TrainingEffect(3.1);

        $this->assertTrue($Effect->isKnown());
        $this->assertEqualsWithDelta(3.1, $Effect->value(), PHP_FLOAT_EPSILON);
        $this->assertSame(TrainingEffectLevel::IMPROVING, $Effect->level());

        $this->assertEqualsWithDelta(2.9, $Effect->set(2.9)->value(), PHP_FLOAT_EPSILON);
        $this->assertSame(TrainingEffectLevel::MAINTAINING, $Effect->level());

        $this->assertFalse($Effect->set(null)->isKnown());
    }

    public function testFormattingValues(): void
    {
        $this->assertSame('', TrainingEffect::format(null));
        $this->assertEquals('3.1', TrainingEffect::format(3.14));
        $this->assertEquals('5.0', TrainingEffect::format(5.0));
    }

    public function testUnknownValue(): void
    {
        $Effect = new TrainingEffect();

        $this->assertFalse($Effect->isKnown());
        $this->assertSame('', $Effect->string());
        $this->assertSame('', $Effect->shortDescription());
        $this->assertSame('', $Effect->description());
        $this->assertEquals(0, $Effect->numericValue());
        $this->assertNull($Effect->value());
        $this->assertNull($Effect->level());
    }
}
