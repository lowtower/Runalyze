<?php

declare(strict_types=1);

namespace Runalyze\Activity;

final class GroundcontactBalanceTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticFunction(): void
    {
        $this->assertSame('49.5L/50.5R&nbsp;%', GroundcontactBalance::format(4950, true));
        $this->assertSame('49.5L/50.5R', GroundcontactBalance::format(4950, false));
    }

    public function testSetter(): void
    {
        $Balance = new GroundcontactBalance(4950);

        $this->assertEqualsWithDelta(49.5, $Balance->leftInPercent(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(50.5, $Balance->rightInPercent(), PHP_FLOAT_EPSILON);

        $Balance->set(5000);
        $this->assertEqualsWithDelta(50.0, $Balance->leftInPercent(), PHP_FLOAT_EPSILON);

        $Balance->setPercent(50.5);
        $this->assertEqualsWithDelta(50.5, $Balance->leftInPercent(), PHP_FLOAT_EPSILON);
        $this->assertSame(5050, $Balance->value());
    }

    public function testIsKnown(): void
    {
        $Balance = new GroundcontactBalance(0);
        $this->assertFalse($Balance->isKnown());

        $Balance->set(5050);
        $this->assertTrue($Balance->isKnown());
    }
}
