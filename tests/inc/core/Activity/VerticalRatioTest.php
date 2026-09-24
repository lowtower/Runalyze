<?php

declare(strict_types=1);

namespace Runalyze\Activity;

#[\PHPUnit\Framework\Attributes\Group('default')]
class VerticalRatioTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticFunction(): void
    {
        $this->assertEquals('7.9&nbsp;%', VerticalRatio::format(79, true));
        $this->assertEquals('6.8', VerticalRatio::format(68, false));
    }

    public function testSetter(): void
    {
        $Ratio = new VerticalRatio(49);

        $this->assertEquals(49, $Ratio->value());
        $this->assertEquals(4.9, $Ratio->inPercent());

        $Ratio->set(50);
        $this->assertEquals(5.0, $Ratio->inPercent());

        $Ratio->setPercent(5.5);
        $this->assertEquals(5.5, $Ratio->inPercent());
        $this->assertEquals(55, $Ratio->value());
    }

    public function testSetPercentRoundsInsteadOfTruncating(): void
    {
        $Ratio = new VerticalRatio();
        $Ratio->setPercent(4.97);

        $this->assertSame(50, $Ratio->value());
    }

    public function testSetAcceptsNumericString(): void
    {
        $Ratio = new VerticalRatio();
        $Ratio->set('49');

        $this->assertSame(49, $Ratio->value());
    }

    public function testDefaultConstructorResultsInZeroValue(): void
    {
        $Ratio = new VerticalRatio();

        $this->assertSame(0, $Ratio->value());
        $this->assertSame(0.0, $Ratio->inPercent());
    }

    public function testUnitReturnsPercentSign(): void
    {
        $Ratio = new VerticalRatio(49);

        $this->assertSame('%', $Ratio->unit());
    }

    public function testStringReturnsEmptyStringForZeroValue(): void
    {
        $Ratio = new VerticalRatio(0);

        $this->assertSame('', $Ratio->string());
        $this->assertSame('', $Ratio->string(false));
    }

    public function testStringReturnsEmptyStringForNegativeValue(): void
    {
        $Ratio = new VerticalRatio(-15);

        $this->assertSame('', $Ratio->string());
    }

    public function testStringWithoutUnitOmitsPercentSign(): void
    {
        $Ratio = new VerticalRatio(79);

        $this->assertSame('7.9', $Ratio->string(false));
    }
}
