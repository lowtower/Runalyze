<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

final readonly class GoalsTrainingDataProvider
{
    public function __construct(private TrainingRepository $trainingRepository) {}

    /**
     * @return array{activity_count: int, distance_sum: float}
     */
    public function getTotalsSince(Account $account, int $sportId, DateTimeInterface $rangeStart): array
    {
        // Training timestamps represent wall-clock time in UTC, without timezone offsets.
        $fromTimestamp = new DateTimeImmutable(
            $rangeStart->format('Y-m-d H:i:s'),
            new DateTimeZone('UTC')
        );

        return $this->trainingRepository->findTrainingTotalsSince(
            $account,
            $sportId,
            $fromTimestamp->getTimestamp()
        );
    }
}
