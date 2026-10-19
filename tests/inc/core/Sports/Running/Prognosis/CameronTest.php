<?php

declare(strict_types=1);

namespace Runalyze\Sports\Running\Prognosis;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class CameronTest extends TestCase
{
    protected Cameron $Cameron;

    protected function setUp(): void
    {
        $this->Cameron = new Cameron();
    }

    public function testWithoutReferenceTime(): void
    {
        $this->assertFalse($this->Cameron->areValuesValid());
    }

    public function testSimplePrognosis(): void
    {
        $this->Cameron->setReferenceResult(2.0, 14 * 60 + 20);

        $this->assertTrue($this->Cameron->areValuesValid());
        $this->assertEqualsWithDelta(22.357 * 60, $this->Cameron->getSeconds(3.0), 0.1);
    }

    public function testMyCurrentResult(): void
    {
        $this->Cameron->setReferenceResult(5.0, 16 * 60 + 32);

        $this->assertTrue($this->Cameron->areValuesValid());
        $this->assertEqualsWithDelta(9 * 60 + 33, $this->Cameron->getSeconds(3.0), 1);
        $this->assertEqualsWithDelta(16 * 60 + 32, $this->Cameron->getSeconds(5.0), 1);
        $this->assertEqualsWithDelta(34 * 60 + 26, $this->Cameron->getSeconds(10.0), 1);
        $this->assertEqualsWithDelta(60 * 60 + 15 * 60 + 56, $this->Cameron->getSeconds(21.1), 1);
        $this->assertEqualsWithDelta(2 * 60 * 60 + 41 * 60 + 22, $this->Cameron->getSeconds(42.2), 1);
    }

    public function testAreValuesValidIsTrueForPositiveReferenceResult(): void
    {
        $Cameron = new Cameron(2550.0, 10.0);

        $this->assertTrue($Cameron->areValuesValid());
    }

    public function testAreValuesValidIsFalseByDefault(): void
    {
        $Cameron = new Cameron();

        $this->assertFalse($Cameron->areValuesValid());
    }

    public function testGetSecondsReturnsReferenceResultUnchangedForReferenceDistance(): void
    {
        $Cameron = new Cameron(2550.0, 10.0);

        $this->assertEqualsWithDelta(2550.0, $Cameron->getSeconds(10.0), 0.01);
    }

    public function testGetSecondsPredictsShorterDistance(): void
    {
        $Cameron = new Cameron(2550.0, 10.0);

        $this->assertEqualsWithDelta(1224.17, $Cameron->getSeconds(5.0), 0.01);
    }

    public function testGetSecondsPredictsHalfMarathonDistance(): void
    {
        $Cameron = new Cameron(2550.0, 10.0);

        $this->assertEqualsWithDelta(5622.21, $Cameron->getSeconds(21.0975), 0.01);
    }

    public function testGetSecondsReturnsNullForZeroDistance(): void
    {
        $Cameron = new Cameron(2550.0, 10.0);

        $this->assertNull($Cameron->getSeconds(0.0));
    }

    public function testGetSecondsReturnsNullWhenValuesAreNotValid(): void
    {
        $Cameron = new Cameron();

        $this->assertNull($Cameron->getSeconds(10.0));
    }
}
