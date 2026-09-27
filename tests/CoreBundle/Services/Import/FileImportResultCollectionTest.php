<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResultCollection;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use stdClass;

#[\PHPUnit\Framework\Attributes\Group('default')]
class FileImportResultCollectionTest extends TestCase
{
    public function testArrayAccessAndIterator(): void
    {
        $collection = new FileImportResultCollection();
        $collection[] = new FileImportResult([], 'a.foo', 'a.foo');
        $collection[] = new FileImportResult([], 'b.foo', 'a.foo');
        $collection[] = new FileImportResult([], 'a.bar', 'b.bar');
        $collection[] = new FileImportResult([
            new ActivityDataContainer(), new ActivityDataContainer(), new ActivityDataContainer(),
        ], 'b.bar', 'b.bar');
        $collection[] = new FileImportResult([], 'c.bar', 'c.bar');
        unset($collection[4]);

        $this->assertEquals(4, count($collection));
        $this->assertEquals(3, $collection->getTotalNumberOfActivities());

        foreach ($collection as $result) {
            $this->assertEquals('.', substr($result->getFileName(), 1, 1));
        }
    }

    public function testFileNames(): void
    {
        $collection = new FileImportResultCollection([
            new FileImportResult([], 'a.foo', 'a.foo'),
            new FileImportResult([], 'b.foo', 'a.foo'),
            new FileImportResult([], 'b.bar', 'c.bar'),
            new FileImportResult([], 'c.bar', 'c.bar'),
        ]);

        $this->assertEquals(['b.foo', 'b.bar'], $collection->getAllConvertedFileNames());
        $this->assertEquals(['a.foo', 'c.bar'], $collection->getAllOriginalFileNames());
    }

    private function makeResult(string $fileName, string $originalFileName, int $numberOfContainers = 1): FileImportResult
    {
        $containers = [];
        for ($i = 0; $i < $numberOfContainers; ++$i) {
            $containers[] = new ActivityDataContainer();
        }

        return new FileImportResult($containers, $fileName, $originalFileName);
    }

    public function testIsEmptyByDefault(): void
    {
        $collection = new FileImportResultCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
    }

    public function testAddAppendsAResult(): void
    {
        $collection = new FileImportResultCollection();
        $result = $this->makeResult('a.gpx', 'a.gpx');

        $collection->add($result);

        $this->assertFalse($collection->isEmpty());
        $this->assertCount(1, $collection);
        $this->assertSame($result, $collection[0]);
    }

    public function testConstructorAcceptsInitialElements(): void
    {
        $result = $this->makeResult('a.gpx', 'a.gpx');

        $collection = new FileImportResultCollection([$result]);

        $this->assertCount(1, $collection);
        $this->assertSame($result, $collection[0]);
    }

    public function testOffsetSetRejectsNonFileImportResultValues(): void
    {
        $collection = new FileImportResultCollection();

        $this->expectException(InvalidArgumentException::class);
        $collection[] = new stdClass();
    }

    public function testOffsetUnsetRemovesAnElement(): void
    {
        $result = $this->makeResult('a.gpx', 'a.gpx');
        $collection = new FileImportResultCollection([$result]);

        unset($collection[0]);

        $this->assertFalse($collection->offsetExists(0));
    }

    public function testMergeCombinesElementsOfBothCollections(): void
    {
        $collectionA = new FileImportResultCollection([$this->makeResult('a.gpx', 'a.gpx')]);
        $collectionB = new FileImportResultCollection([$this->makeResult('b.gpx', 'b.gpx')]);

        $collectionA->merge($collectionB);

        $this->assertCount(2, $collectionA);
    }

    public function testGetTotalNumberOfActivitiesSumsAcrossAllResults(): void
    {
        $collection = new FileImportResultCollection([
            $this->makeResult('a.gpx', 'a.gpx', 2),
            $this->makeResult('b.gpx', 'b.gpx', 3),
        ]);

        $this->assertSame(5, $collection->getTotalNumberOfActivities());
    }

    public function testGetTotalNumberOfActivitiesIgnoresFailedResults(): void
    {
        $failed = new FileImportResult([new ActivityDataContainer()], 'a.gpx', 'a.gpx', new ParserException('boom'));
        $collection = new FileImportResultCollection([$failed, $this->makeResult('b.gpx', 'b.gpx', 1)]);

        $this->assertSame(1, $collection->getTotalNumberOfActivities());
    }

    public function testGetAllFileNames(): void
    {
        $collection = new FileImportResultCollection([
            $this->makeResult('converted-a.gpx', 'a.fit'),
            $this->makeResult('converted-b.gpx', 'b.fit'),
        ]);

        $this->assertSame(['converted-a.gpx', 'converted-b.gpx'], $collection->getAllFileNames());
    }

    public function testGetAllOriginalFileNamesDeduplicates(): void
    {
        $collection = new FileImportResultCollection([
            $this->makeResult('a1.gpx', 'a.zip'),
            $this->makeResult('a2.gpx', 'a.zip'),
            $this->makeResult('b.gpx', 'b.zip'),
        ]);

        $this->assertSame(['a.zip', 'b.zip'], $collection->getAllOriginalFileNames());
    }

    public function testGetAllConvertedFileNamesOnlyIncludesFilesWhoseNameChanged(): void
    {
        $collection = new FileImportResultCollection([
            $this->makeResult('converted.gpx', 'original.fit'),
            $this->makeResult('unchanged.gpx', 'unchanged.gpx'),
        ]);

        $this->assertSame(['converted.gpx'], $collection->getAllConvertedFileNames());
    }

    public function testReindexResetsArrayKeysAfterUnset(): void
    {
        $first = $this->makeResult('a.gpx', 'a.gpx');
        $second = $this->makeResult('b.gpx', 'b.gpx');
        $collection = new FileImportResultCollection([$first, $second]);

        unset($collection[0]);
        $collection->reindex();

        $this->assertSame($second, $collection[0]);
    }

    public function testIterationVisitsAllElementsInOrder(): void
    {
        $first = $this->makeResult('a.gpx', 'a.gpx');
        $second = $this->makeResult('b.gpx', 'b.gpx');
        $collection = new FileImportResultCollection([$first, $second]);

        $visited = [];
        foreach ($collection as $result) {
            $visited[] = $result;
        }

        $this->assertSame([$first, $second], $visited);
    }
}
