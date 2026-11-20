<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

final readonly class PrognoseCalculatorPersonalBestProvider
{
    public function __construct(
        private RaceresultRepository $raceResultRepository,
        private TrainingRepository $trainingRepository
    ) {}

    /**
     * @param array<int, float|string> $distances
     *
     * @return array<string, array{seconds: float, activity_id: int, timestamp: int}>
     */
    public function findForDistances(Account $account, int $sportId, array $distances): array
    {
        $results = $this->raceResultRepository->findPersonalBestDetailsForDistances($account, $sportId, $distances);
        $personalBests = [];

        foreach ($results as $distance => $result) {
            $activity = $this->trainingRepository->findForAccount($result['activity_id'], $account->getId());

            if ($activity === null) {
                continue;
            }

            $personalBests[$distance] = [
                'seconds' => $result['seconds'],
                'activity_id' => $result['activity_id'],
                'timestamp' => $activity->getTime(),
            ];
        }

        return $personalBests;
    }
}
