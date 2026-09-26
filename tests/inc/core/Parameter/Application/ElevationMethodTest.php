<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ElevationMethodTest extends \PHPUnit\Framework\TestCase
{
    protected ElevationMethod $object;

    protected function setUp(): void
    {
        $this->object = new ElevationMethod();
    }

    public function testDefaultValueIsThreshold(): void
    {
        self::assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        self::assertTrue($this->object->usesThreshold());
        self::assertFalse($this->object->usesNone());
        self::assertFalse($this->object->usesDouglasPeucker());
        self::assertFalse($this->object->usesReumannWitkam());
    }

    public function testNoneMethod(): void
    {
        $this->object->set(ElevationMethod::NONE);

        self::assertSame(ElevationMethod::NONE, $this->object->value());
        self::assertTrue($this->object->usesNone());
        self::assertFalse($this->object->usesThreshold());
        self::assertFalse($this->object->usesDouglasPeucker());
    }

    public function testThresholdMethod(): void
    {
        $this->object->set(ElevationMethod::THRESHOLD);

        self::assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        self::assertFalse($this->object->usesNone());
        self::assertTrue($this->object->usesThreshold());
        self::assertFalse($this->object->usesDouglasPeucker());
    }

    public function testDouglasPeuckerMethod(): void
    {
        $this->object->set(ElevationMethod::DOUGLAS_PEUCKER);

        self::assertSame(ElevationMethod::DOUGLAS_PEUCKER, $this->object->value());
        self::assertFalse($this->object->usesNone());
        self::assertFalse($this->object->usesThreshold());
        self::assertTrue($this->object->usesDouglasPeucker());
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

        self::assertSame(ElevationMethod::THRESHOLD, $this->object->value());
        self::assertFalse($this->object->usesReumannWitkam());
    }
}
