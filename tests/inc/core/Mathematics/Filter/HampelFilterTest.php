<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\Filter;

use Runalyze\Mathematics\Filter\HampelFilter;

final class HampelFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HampelFilter
     */
    protected $Filter;

    protected function setUp(): void
    {
        $this->Filter = new HampelFilter(3);
    }

    public function testInvalidWindowWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HampelFilter(0);
    }

    public function testNonIntegerWindowWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HampelFilter(3.14);
    }

    public function testShortArrays(): void
    {
        $this->assertEquals([], $this->Filter->filter([], true));
        $this->assertEquals([1234], $this->Filter->filter([1234], true));
        $this->assertEquals([10, 100, 10, 10, 100, 10], $this->Filter->filter([10, 100, 10, 10, 100, 10], true));
    }

    public function testTrivialExample(): void
    {
        $this->assertEquals([3], $this->Filter->filter([10, 10, 10, 100, 10, 10, 10]));
        $this->assertEquals([10, 10, 10, 10, 10, 10, 10], $this->Filter->filter([10, 10, 10, 100, 10, 10, 10], true));
    }

    public function testDifferentWindowSizes(): void
    {
        $data = [10, 11, 12, 13, 50, 12, 100, 11, 50, 10, 11, 12, 13];

        $this->assertEquals([4, 6, 8, 9], new HampelFilter(1)->filter($data, false));
        $this->assertEquals([4, 6, 8], new HampelFilter(2)->filter($data, false));
        $this->assertEquals([4, 6, 8], new HampelFilter(3)->filter($data, false));
        $this->assertEquals([4, 6, 8], new HampelFilter(4)->filter($data, false));
        $this->assertEquals([6], new HampelFilter(5)->filter($data, false));
    }
}
