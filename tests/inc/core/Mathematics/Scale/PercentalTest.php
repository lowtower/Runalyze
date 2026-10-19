<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Scale;

use Runalyze\Mathematics\Scale\Percental;

final class PercentalTest extends \PHPUnit\Framework\TestCase
{
    protected Percental $Scale;

    protected function setUp(): void
    {
        $this->Scale = new Percental();
    }

    public function testNoTransformation(): void
    {
        $this->assertEquals(26, $this->Scale->transform(26));
    }

    public function testMinMax(): void
    {
        $this->assertEquals(0, $this->Scale->transform(-10));
        $this->assertEquals(0, $this->Scale->transform(0));
        $this->assertEquals(100, $this->Scale->transform(100));
        $this->assertEquals(100, $this->Scale->transform(120));
    }

    public function testNewMinimum(): void
    {
        $this->Scale->setMinimum(-400);

        $this->assertEquals(40, $this->Scale->transform(-200));
        $this->assertEquals(80, $this->Scale->transform(0));
    }

    public function testNewMaximum(): void
    {
        $this->Scale->setMaximum(200);

        $this->assertEquals(50, $this->Scale->transform(100));
        $this->assertEquals(75, $this->Scale->transform(150));
        $this->assertEquals(100, $this->Scale->transform(210));
    }

    public function testNewScale(): void
    {
        $this->Scale->setMinimum(1);
        $this->Scale->setMaximum(10);

        $this->assertEqualsWithDelta(0, $this->Scale->transform(0.9), 0.0001);
        $this->assertEqualsWithDelta(5, $this->Scale->transform(1.45), 0.0001);
        $this->assertEqualsWithDelta(50, $this->Scale->transform(5.5), 0.0001);
        $this->assertEqualsWithDelta(69, $this->Scale->transform(7.2), 0.5);
        $this->assertEqualsWithDelta(89, $this->Scale->transform(9.0), 0.5);
    }

    public function testReturnsZeroForInvalidScaleWithEqualMinAndMax(): void
    {
        $this->Scale->setMinimum(10);
        $this->Scale->setMaximum(10);

        $this->assertEqualsWithDelta(0.0, $this->Scale->transform(10), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->Scale->transform(20), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->Scale->transform(-20), PHP_FLOAT_EPSILON);
    }
}
