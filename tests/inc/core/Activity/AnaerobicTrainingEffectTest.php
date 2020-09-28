<?php

namespace Runalyze\Activity;

class AnaerobicTrainingEffectTest extends \PHPUnit\Framework\TestCase
{
    public function testLowerBoundary()
    {
        $effect = new TrainingEffect(0.0);

        $this->assertSame(0.0, $effect->value());
    }

    public function testUpperBoundary()
    {
        $effect = new TrainingEffect(5.0);

        $this->assertSame(5.0, $effect->value());
    }

    /** @expectedException \InvalidArgumentException */
	public function testInvalidValueTooSmall()
	{
        new AnaerobicTrainingEffect(-0.1);
	}

    /** @expectedException \InvalidArgumentException */
    public function testInvalidValueTooBig()
    {
        new AnaerobicTrainingEffect(5.1);
    }

    /** @expectedException \InvalidArgumentException */
    public function testInvalidValueNonNumeric()
    {
        new AnaerobicTrainingEffect(false);
    }

    /** @expectedException \InvalidArgumentException */
    public function testInvalidValueString()
    {
        new AnaerobicTrainingEffect('foobar');
    }

    public function testValidValueZero()
    {
        $effect = new AnaerobicTrainingEffect(0.0);

        $this->assertSame(0.0, $effect->value());
    }

    public function testValidCommaValue()
    {
        $effect = new AnaerobicTrainingEffect('3,5');

        $this->assertSame(3.5, $effect->value());
    }

    public function testSimpleValue()
    {
        $Effect = new AnaerobicTrainingEffect(3.1);

        $this->assertTrue($Effect->isKnown());
        $this->assertEquals(3.1, $Effect->value());
        $this->assertEquals(TrainingEffectLevel::IMPROVING, $Effect->level());

        $this->assertEquals(2.9, $Effect->set(2.9)->value());
        $this->assertEquals(TrainingEffectLevel::MAINTAINING, $Effect->level());

        $this->assertFalse($Effect->set(null)->isKnown());
    }

    public function testFormattingValues()
    {
        $this->assertEquals('', AnaerobicTrainingEffect::format(null));
        $this->assertEquals('3.1', AnaerobicTrainingEffect::format(3.14));
        $this->assertEquals('5.0', AnaerobicTrainingEffect::format(5.0));
    }

    public function testUnknownValue()
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
}
