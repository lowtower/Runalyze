<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Runalyze\Bundle\CoreBundle\Controller\Activity\CreateController;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResult;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImportResultCollection;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

#[\PHPUnit\Framework\Attributes\Group('default')]
class CreateControllerFindFirstActivityDataContainerTest extends TestCase
{
    private function invokeFindFirstActivityDataContainer(FileImportResultCollection $results): ActivityDataContainer
    {
        $controller = (new ReflectionClass(CreateController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(CreateController::class, 'findFirstActivityDataContainer');

        return $method->invoke($controller, $results);
    }

    public function testReturnsTheOnlyContainerWhenTheFirstResultSucceeded(): void
    {
        $container = new ActivityDataContainer();
        $results = new FileImportResultCollection([
            new FileImportResult([$container], 'a.fit', 'a.fit'),
        ]);

        $this->assertSame($container, $this->invokeFindFirstActivityDataContainer($results));
    }

    public function testSkipsAnEarlierFailedResultAndReturnsTheContainerOfALaterSuccessfulResult(): void
    {
        $container = new ActivityDataContainer();
        $results = new FileImportResultCollection([
            new FileImportResult([], 'a.fit', 'a.fit', new ParserException('could not parse a.fit')),
            new FileImportResult([$container], 'b.fit', 'b.fit'),
        ]);

        $this->assertSame($container, $this->invokeFindFirstActivityDataContainer($results));
    }

    public function testThrowsWhenNoResultInTheCollectionContainsAnActivity(): void
    {
        $results = new FileImportResultCollection([
            new FileImportResult([], 'a.fit', 'a.fit', new ParserException('could not parse a.fit')),
        ]);

        $this->expectException(LogicException::class);

        $this->invokeFindFirstActivityDataContainer($results);
    }
}
