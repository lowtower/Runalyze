<?php

namespace Runalyze\Activity;

class TrainingEffectLevelTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidLevelTooSmall(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(0.9);
    }

    public function testInvalidLevelTooBig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(5.1);
    }

    public function testInvalidLevelNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::levelFor(false);
    }

    public function testInvalidLevelString(): void
    {
        $this->expectException(\TypeError::class);
        TrainingEffectLevel::levelFor('foobar');
    }

    public function testValidLevels(): void
    {
        $this->assertSame(TrainingEffectLevel::EASY, TrainingEffectLevel::levelFor(1.0));
        $this->assertSame(TrainingEffectLevel::EASY, TrainingEffectLevel::levelFor(1.9));
        $this->assertSame(TrainingEffectLevel::MAINTAINING, TrainingEffectLevel::levelFor(2.0));
        $this->assertSame(TrainingEffectLevel::MAINTAINING, TrainingEffectLevel::levelFor(2.9));
        $this->assertSame(TrainingEffectLevel::IMPROVING, TrainingEffectLevel::levelFor(3.0));
        $this->assertSame(TrainingEffectLevel::IMPROVING, TrainingEffectLevel::levelFor(3.9));
        $this->assertSame(TrainingEffectLevel::HIGHLY_IMPROVING, TrainingEffectLevel::levelFor(4.0));
        $this->assertSame(TrainingEffectLevel::HIGHLY_IMPROVING, TrainingEffectLevel::levelFor(4.9));
        $this->assertSame(TrainingEffectLevel::OVERREACHING, TrainingEffectLevel::levelFor(5.0));
    }

    public function testLabelForInvalidLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::label(0);
    }

    public function testDescriptionForInvalidLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TrainingEffectLevel::description(TrainingEffectLevel::OVERREACHING + 1);
    }

    public function testThatLabelAndDescriptionAreDefinedForAllLevels(): void
    {
        foreach (TrainingEffectLevel::getEnum() as $level) {
            $this->assertNotEmpty(TrainingEffectLevel::label($level));
            $this->assertNotEmpty(TrainingEffectLevel::description($level));
        }
    }
}
