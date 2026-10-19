<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class PaceAxisTypeTest extends TestCase
{
    private PaceAxisType $type;

    protected function setUp(): void
    {
        $this->type = new PaceAxisType();
    }

    public function testDefaultValueIsAsSpeed(): void
    {
        $this->assertSame(PaceAxisType::AS_SPEED, $this->type->value());
    }

    public function testAllDocumentedOptionsAreAccepted(): void
    {
        foreach ([PaceAxisType::LINEAR, PaceAxisType::REVERSE, PaceAxisType::AS_SPEED] as $option) {
            $this->type->set($option);

            $this->assertSame($option, $this->type->value());
        }
    }

    public function testOptionsContainAllThreeChoices(): void
    {
        $this->assertSame([PaceAxisType::LINEAR, PaceAxisType::REVERSE, PaceAxisType::AS_SPEED], array_keys($this->type->options()));
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->type->set(PaceAxisType::LINEAR);
        $this->type->set('unknown');

        $this->assertSame(PaceAxisType::AS_SPEED, $this->type->value());
    }
}
