<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use Exception;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

#[\PHPUnit\Framework\Attributes\Group('default')]
class FileImportResultTest extends TestCase
{
    public function testFailedResult(): void
    {
        $result = new FileImportResult([], 'foo.bar', 'bar.foo', new Exception('Test'));

        $this->assertTrue($result->isFailed());
        $this->assertSame('Test', $result->getException()->getMessage());
    }

    public function testEmptyResult(): void
    {
        $result = new FileImportResult([], 'foo.bar', 'bar.foo');

        $this->assertFalse($result->isFailed());
        $this->assertSame(0, $result->getNumberOfActivities());
        $this->assertEmpty($result->getContainer());
        $this->assertSame('foo.bar', $result->getFileName());
        $this->assertSame('bar.foo', $result->getOriginalFileName());
    }

    public function testSimpleResult(): void
    {
        $containerA = new ActivityDataContainer();
        $containerA->Metadata->setDescription('A');

        $containerB = new ActivityDataContainer();
        $containerB->Metadata->setDescription('B');

        $result = new FileImportResult([$containerA, $containerB], 'foo.bar', 'bar.foo');

        $this->assertSame(2, $result->getNumberOfActivities());
        $this->assertSame('A', $result->getContainer(0)->Metadata->getDescription());
        $this->assertSame('B', $result->getContainer(1)->Metadata->getDescription());
        $this->assertCount(2, $result->getContainer());
    }

    public function testGetFileNameAndOriginalFileName(): void
    {
        $result = new FileImportResult([], 'converted.gpx', 'original.fit');

        $this->assertSame('converted.gpx', $result->getFileName());
        $this->assertSame('original.fit', $result->getOriginalFileName());
    }

    public function testIsNotFailedWithoutAnException(): void
    {
        $result = new FileImportResult([new ActivityDataContainer()], 'file.gpx', 'file.gpx');

        $this->assertFalse($result->isFailed());
        $this->assertNull($result->getException());
    }

    public function testIsFailedWithAnException(): void
    {
        $exception = new ParserException('boom');
        $result = new FileImportResult([], 'file.gpx', 'file.gpx', $exception);

        $this->assertTrue($result->isFailed());
        $this->assertSame($exception, $result->getException());
    }

    public function testGetNumberOfActivitiesCountsTheContainers(): void
    {
        $result = new FileImportResult(
            [new ActivityDataContainer(), new ActivityDataContainer()],
            'file.gpx',
            'file.gpx'
        );

        $this->assertSame(2, $result->getNumberOfActivities());
    }

    public function testGetNumberOfActivitiesIsZeroWhenFailedRegardlessOfContainerCount(): void
    {
        $result = new FileImportResult(
            [new ActivityDataContainer()],
            'file.gpx',
            'file.gpx',
            new ParserException('boom')
        );

        $this->assertSame(0, $result->getNumberOfActivities());
    }

    public function testGetContainerWithoutIndexReturnsAllContainers(): void
    {
        $containers = [new ActivityDataContainer(), new ActivityDataContainer()];
        $result = new FileImportResult($containers, 'file.gpx', 'file.gpx');

        $this->assertSame($containers, $result->getContainer());
    }

    public function testGetContainerWithIndexReturnsTheSpecificContainer(): void
    {
        $first = new ActivityDataContainer();
        $second = new ActivityDataContainer();
        $result = new FileImportResult([$first, $second], 'file.gpx', 'file.gpx');

        $this->assertSame($second, $result->getContainer(1));
    }

    public function testGetContainerWithUnknownIndexFallsBackToAllContainers(): void
    {
        $containers = [new ActivityDataContainer()];
        $result = new FileImportResult($containers, 'file.gpx', 'file.gpx');

        $this->assertSame($containers, $result->getContainer(5));
    }

    public function testCompleteAndFilterContainerDoesNothingWhenAlreadyFailed(): void
    {
        $exception = new ParserException('boom');
        $result = new FileImportResult([], 'file.gpx', 'file.gpx', $exception);

        $filter = $this->createMock(ActivityDataContainerFilter::class);
        $filter->expects($this->never())
            ->method('filter');

        $result->completeAndFilterContainer($filter);

        $this->assertSame($exception, $result->getException());
    }

    public function testCompleteAndFilterContainerRemovesContainersThatFailFiltering(): void
    {
        $keptContainer = new ActivityDataContainer();
        $removedContainer = new ActivityDataContainer();
        $result = new FileImportResult([$keptContainer, $removedContainer], 'file.gpx', 'file.gpx');

        $filterException = new ParserException('invalid data');
        $filter = $this->createMock(ActivityDataContainerFilter::class);
        $filter->method('filter')
            ->willReturnCallback(function (ActivityDataContainer $container) use ($removedContainer, $filterException): void {
                if ($container === $removedContainer) {
                    throw $filterException;
                }
            });

        $result->completeAndFilterContainer($filter);

        $this->assertTrue($result->isFailed());
        $this->assertSame($filterException, $result->getException());
        $this->assertSame([$keptContainer], $result->getContainer());
    }
}
