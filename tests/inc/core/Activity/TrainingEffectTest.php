<?php

namespace Runalyze\Activity;

class TrainingEffectTest extends \PHPUnit\Framework\TestCase
{
    public function testLowerBoundary(): void
    {
        $effect = new TrainingEffect(0.0);

        $this->assertSame(0.0, $effect->value());
    }

    public function testUpperBoundary(): void
    {
        $effect = new TrainingEffect(5.0);

        $this->assertSame(5.0, $effect->value());
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

        $this->assertSame(0.0, $effect->value());
    }

    public function testValidCommaValue(): void
    {
        $effect = new TrainingEffect('3,5');

        $this->assertSame(3.5, $effect->value());
    }

    public function testSimpleValue(): void
    {
        $Effect = new TrainingEffect(3.1);

        $this->assertTrue($Effect->isKnown());
        $this->assertEquals(3.1, $Effect->value());
        $this->assertEquals(TrainingEffectLevel::IMPROVING, $Effect->level());

        $this->assertEquals(2.9, $Effect->set(2.9)->value());
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, $Effect->level());

        $this->assertFalse($Effect->set(null)->isKnown());
    }

    public function testFormattingValues(): void
    {
        $this->assertEquals('', TrainingEffect::format(null));
        $this->assertEquals('3.1', TrainingEffect::format(3.14));
        $this->assertEquals('5.0', TrainingEffect::format(5.0));
    }

    public function testUnknownValue(): void
    {
        $Effect = new TrainingEffect();

        $this->assertFalse($Effect->isKnown());
        $this->assertEquals('', $Effect->string());
        $this->assertEquals('', $Effect->shortDescription());
        $this->assertEquals('', $Effect->description());
        $this->assertEquals(0, $Effect->numericValue());
        $this->assertNull($Effect->value());
        $this->assertNull($Effect->level());
    }
}
