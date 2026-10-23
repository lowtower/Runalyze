<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Scale;

use Runalyze\Mathematics\Scale\TwoPartPercental;

final class TwoPartPercentalTest extends \PHPUnit\Framework\TestCase
{
    protected TwoPartPercental $Scale;

    protected function setUp(): void
    {
        $this->Scale = new TwoPartPercental();
    }

    protected function tearDown(): void {}

    public function testNoTransformation(): void
    {
        $this->assertEqualsWithDelta(0, $this->Scale->transform(-10), 0.0001);
        $this->assertEqualsWithDelta(26, $this->Scale->transform(26), 0.0001);
        $this->assertEqualsWithDelta(50, $this->Scale->transform(50), 0.0001);
        $this->assertEqualsWithDelta(100, $this->Scale->transform(120), 0.0001);
    }

    public function testSimpleScale(): void
    {
        $this->Scale->setMinimum(1);
        $this->Scale->setInflectionPoint(2);
        $this->Scale->setMaximum(10);

        $this->assertEquals(0, $this->Scale->transform(1));
        $this->assertEqualsWithDelta(5, $this->Scale->transform(1.1), 0.0001);
        $this->assertEqualsWithDelta(25, $this->Scale->transform(1.5), 0.0001);
        $this->assertEqualsWithDelta(50, $this->Scale->transform(2.0), 0.0001);
        $this->assertEqualsWithDelta(50, $this->Scale->transform(2.0), 0.0001);
        $this->assertEqualsWithDelta(56, $this->Scale->transform(3.0), 0.5);
        $this->assertEqualsWithDelta(75, $this->Scale->transform(6.0), 0.0001);
    }

    public function testValueBelowMinimumIsClampedToZero(): void
    {
        $this->Scale->setMinimum(10);
        $this->Scale->setInflectionPoint(20);
        $this->Scale->setMaximum(100);

        $this->assertEqualsWithDelta(0.0, $this->Scale->transform(5), PHP_FLOAT_EPSILON);
    }

    public function testValueAboveMaximumIsClampedToHundred(): void
    {
        $this->Scale->setMinimum(10);
        $this->Scale->setInflectionPoint(20);
        $this->Scale->setMaximum(100);

        $this->assertEqualsWithDelta(100.0, $this->Scale->transform(150), PHP_FLOAT_EPSILON);
    }
}
