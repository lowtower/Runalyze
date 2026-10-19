<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ElevationMethodTest extends \PHPUnit\Framework\TestCase
{
    protected ElevationMethod $object;

    protected function setUp(): void
    {
        $this->object = new ElevationMethod();
    }

    public function testDefaultValueIsThreshold(): void
    {
        $this->assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        $this->assertTrue($this->object->usesThreshold());
        $this->assertFalse($this->object->usesNone());
        $this->assertFalse($this->object->usesDouglasPeucker());
        $this->assertFalse($this->object->usesReumannWitkam());
    }

    public function testNoneMethod(): void
    {
        $this->object->set(ElevationMethod::NONE);

        $this->assertSame(ElevationMethod::NONE, $this->object->value());
        $this->assertTrue($this->object->usesNone());
        $this->assertFalse($this->object->usesThreshold());
        $this->assertFalse($this->object->usesDouglasPeucker());
    }

    public function testThresholdMethod(): void
    {
        $this->object->set(ElevationMethod::THRESHOLD);

        $this->assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        $this->assertFalse($this->object->usesNone());
        $this->assertTrue($this->object->usesThreshold());
        $this->assertFalse($this->object->usesDouglasPeucker());
    }

    public function testDouglasPeuckerMethod(): void
    {
        $this->object->set(ElevationMethod::DOUGLAS_PEUCKER);

        $this->assertSame(ElevationMethod::DOUGLAS_PEUCKER, $this->object->value());
        $this->assertFalse($this->object->usesNone());
        $this->assertFalse($this->object->usesThreshold());
        $this->assertTrue($this->object->usesDouglasPeucker());
    }

    /**
     * Reumann-Witkam is defined as a constant and has a dedicated usesReumannWitkam()
     * method, but the corresponding option is commented out in the constructor.
     * It can therefore currently never be selected and silently falls back to the
     * default (THRESHOLD) instead.
     *
     * TODO: Reactivate this option once the algorithm is confirmed to work correctly
     * (see project backlog). This test documents the current, disabled behaviour
     * and should be updated once REUMANN_WITKAM is re-enabled.
     */
    public function testReumannWitkamCannotBeSelectedBecauseOptionIsDisabled(): void
    {
        $this->object->set(ElevationMethod::REUMANN_WITKAM);

        $this->assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        $this->assertFalse($this->object->usesReumannWitkam());
    }
}
