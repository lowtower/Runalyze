<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis;

use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\VO2max;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

class VO2maxAnalysis
{
    public function __construct(
        protected TrainingRepository $trainingRepository,
        protected VO2max $vo2maxConfiguration
    ) {}

    /**
     * @return RaceAnalysis[]
     */
    public function getAnalysisForAllRaces(float $vo2maxFactor, Account $account, int $sportId): array
    {
        $analysisData = [];

        foreach ($this->trainingRepository->findVO2maxAnalysisRaces($account, $sportId) as $training) {
            $analysisData[] = new RaceAnalysis(
                $training,
                $vo2maxFactor,
                $this->loadUncorrectedShape($account, $sportId, $training->getTime()) * $vo2maxFactor
            );
        }

        return $analysisData;
    }

    protected function loadUncorrectedShape(Account $account, int $sportId, int $time): float
    {
        return $this->trainingRepository->calculateVO2maxShape($account, $this->vo2maxConfiguration, $sportId, $time);
    }
}
