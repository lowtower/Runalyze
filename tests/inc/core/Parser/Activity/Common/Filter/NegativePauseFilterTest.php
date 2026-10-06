<?php

namespace Runalyze\Tests\Parser\Activity\Common\Filter;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Activity\Common\Exception\InvalidDataException;
use Runalyze\Parser\Activity\Common\Filter\NegativePauseFilter;

class NegativePauseFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NegativePauseFilter
     */
    protected $Filter;

    /**
     * @var ActivityDataContainer
     */
    protected $Container;

    protected function setUp(): void
    {
        $this->Filter = new NegativePauseFilter();
        $this->Container = new ActivityDataContainer();
    }

    public function testEmptyContainer(): void
    {
        $this->Filter->filter($this->Container);

        $this->assertEmpty($this->Container->Pauses);
    }

    public function testExampleWithoutAnyInvalidPause(): void
    {
        $this->Container->Pauses->add(new Pause(117, 23));
        $this->Container->Pauses->add(new Pause(251, 19));
        $this->Container->Pauses->add(new Pause(506, 2));

        $this->Filter->filter($this->Container);

        $this->assertCount(3, $this->Container->Pauses);
    }

    public function testInvalidPause(): void
    {
        $this->Container->Pauses->add(new Pause(117, -12));
        $this->Container->Pauses->add(new Pause(251, 0));
        $this->Container->Pauses->add(new Pause(506, 2));

        $this->Filter->filter($this->Container);

        $this->assertCount(1, $this->Container->Pauses);
        $this->assertSame([506, 2.0], [$this->Container->Pauses[0]->getTimeIndex(), $this->Container->Pauses[0]->getDuration()]);
    }

    public function testStrictMode(): void
    {
        $this->Container->Pauses->add(new Pause(117, -12));

        $this->expectException(InvalidDataException::class);

        $this->Filter->filter($this->Container, true);
    }

    public function testLogMessageInNonStrictMode(): void
    {
        $handler = new TestHandler();
        $this->Filter->setLogger(new Logger('test', [$handler]));
        $this->Container->Pauses->add(new Pause(117, -12));

        $this->Filter->filter($this->Container);

        $this->assertTrue($handler->hasWarningThatContains('of -12s removed'));
        $this->assertEmpty($this->Container->Pauses);
    }
}
