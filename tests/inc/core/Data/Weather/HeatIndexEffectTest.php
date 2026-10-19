<?php

declare(strict_types=1);

use Runalyze\Data\Weather\HeatIndexEffect;

final class HeatIndexEffectTest extends \PHPUnit\Framework\TestCase
{
    public function testThatDescriptionCanBeCalledForAllLevels(): void
    {
        foreach (HeatIndexEffect::getEnum() as $level) {
            $description = HeatIndexEffect::description($level);
            $this->assertIsString($description, "Description for level {$level} should be a string.");
            $this->assertNotEmpty($description, "Description for level {$level} should not be empty.");
        }
    }

    public function testThatLabelCanBeCalledForAllLevels(): void
    {
        foreach (HeatIndexEffect::getEnum() as $level) {
            $label = HeatIndexEffect::label($level);
            $this->assertIsString($label, "Label for level {$level} should be a string.");
            $this->assertNotEmpty($label, "Label for level {$level} should not be empty.");
        }
    }

    public function testThatIconCanBeCalledForAllLevels(): void
    {
        foreach (HeatIndexEffect::getEnum() as $level) {
            $icon = HeatIndexEffect::icon($level);
            $this->assertIsString($icon, "Icon for level {$level} should be a string.");
            $this->assertStringContainsString('<i', $icon, "Icon for level {$level} should contain an <i> tag.");
            $this->assertStringContainsString('fa', $icon, "Icon for level {$level} should contain a FontAwesome class.");
        }
    }

    public function testSomeLevels(): void
    {
        $this->assertSame(HeatIndexEffect::NO_EFFECT, HeatIndexEffect::levelFor(79));
        $this->assertSame(HeatIndexEffect::CAUTION, HeatIndexEffect::levelFor(90));
        $this->assertSame(HeatIndexEffect::EXTREME_CAUTION, HeatIndexEffect::levelFor(91));
        $this->assertSame(HeatIndexEffect::DANGER, HeatIndexEffect::levelFor(105));
        $this->assertSame(HeatIndexEffect::EXTREME_DANGER, HeatIndexEffect::levelFor(140));
    }
}
