<?php

declare(strict_types=1);

namespace Runalyze\Tests\Parser\Activity\Common\Filter;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Parser\Activity\Common\Exception\InvalidDataException;
use Runalyze\Parser\Activity\Common\Filter\InvalidRRIntervalFilter;

final class InvalidRRIntervalFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvalidRRIntervalFilter
     */
    protected $Filter;

    /**
     * @var ActivityDataContainer
     */
    protected $Container;

    protected function setUp(): void
    {
        $this->Filter = new InvalidRRIntervalFilter();
        $this->Container = new ActivityDataContainer();
    }

    public function testEmptyContainer(): void
    {
        $this->Filter->filter($this->Container);

        $this->assertEquals([], $this->Container->RRIntervals);
    }

    public function testSimpleExample(): void
    {
        $this->Container->RRIntervals = [723, 751, 0, 739, 760, 0, 747];

        $this->Filter->filter($this->Container);

        $this->assertEquals([723, 751, 739, 760, 747], $this->Container->RRIntervals);
    }

    public function testOnlyZeros(): void
    {
        $this->Container->RRIntervals = [0, 0, 0];

        $this->Filter->filter($this->Container);

        $this->assertEquals([], $this->Container->RRIntervals);
    }

    public function testStrictMode(): void
    {
        $this->Container->RRIntervals = [469, 0, 471];

        $this->expectException(InvalidDataException::class);

        $this->Filter->filter($this->Container, true);
    }

    public function testLogMessageInNonStrictMode(): void
    {
        $handler = new TestHandler();
        $this->Filter->setLogger(new Logger('test', [$handler]));
        $this->Container->RRIntervals = [723, 751, 0, 739, 760, 0, 747];

        $this->Filter->filter($this->Container);

        $this->assertTrue($handler->hasWarningThatContains('2 invalid'));
        $this->assertEquals([723, 751, 739, 760, 747], $this->Container->RRIntervals);
    }
}
