<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class DuplicateFinderTest extends TestCase
{
    public function testIsPossibleDuplicateDelegatesToTrainingRepository(): void
    {
        $activity = new Training()->setTime(1700000000);

        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('isPossibleDuplicate')
            ->with($activity)
            ->willReturn(true);

        $finder = new DuplicateFinder($repository);

        $this->assertTrue($finder->isPossibleDuplicate($activity));
    }

    public function testIsPossibleDuplicateReturnsFalseWhenRepositoryReturnsFalse(): void
    {
        $activity = new Training()->setTime(1700000000);

        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('isPossibleDuplicate')
            ->willReturn(false);

        $finder = new DuplicateFinder($repository);

        $this->assertFalse($finder->isPossibleDuplicate($activity));
    }

    public function testSetsActivityIdFromTimestampBeforeDelegatingWhenNotAlreadySet(): void
    {
        $activity = new Training()->setTime(1700000123);
        $this->assertNull($activity->getActivityId());

        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('isPossibleDuplicate')
            ->with($this->callback(fn (Training $passedActivity): bool => $passedActivity->getActivityId() !== null))
            ->willReturn(false);

        $finder = new DuplicateFinder($repository);
        $finder->isPossibleDuplicate($activity);

        $this->assertSame((int) floor(1700000123 / 60.0) * 60, $activity->getActivityId());
    }

    public function testDoesNotOverwriteAnAlreadySetActivityId(): void
    {
        $activity = new Training()
            ->setTime(1700000123)
            ->setActivityId(42);

        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('isPossibleDuplicate')
            ->willReturn(false);

        $finder = new DuplicateFinder($repository);
        $finder->isPossibleDuplicate($activity);

        $this->assertSame(42, $activity->getActivityId());
    }
}
