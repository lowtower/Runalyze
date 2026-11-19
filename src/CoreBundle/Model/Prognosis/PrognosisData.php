<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Prognosis;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;

/**
 * Prognosis data for the prognosis panel.
 *
 * Replaces the former plugin panel methods `lookupPersonalBests()`,
 * `prepareForPrognosis()` and `showPrognosis()`.
 */
class PrognosisData
{
    private readonly PrognosisInterface $prognosis;

    /**
     * @var array<int, array{distance: float, pbSeconds: float|null, pbActivityId: int|null, prognosisSeconds: float}>
     */
    private array $rows = [];

    private readonly int $numberOfPersonalBests;

    /**
     * @param array<int|string, mixed> $distances distances in [km]
     * @param string $model prognosis model, see PrognosisModel
     * @param bool $useMarathonShape use the marathon shape factor (VO2max model only)
     * @param float $vo2maxShape current effective VO2max shape [ml/kg/min]
     * @param int $basicEndurance current basic endurance [%]
     */
    public function __construct(
        TrainingRepository $trainingRepository,
        RaceresultRepository $raceResultRepository,
        Account $account,
        int $runningSportId,
        array $distances,
        string $model,
        bool $useMarathonShape,
        float $vo2maxShape,
        int $basicEndurance
    ) {
        $this->prognosis = new PrognosisModel(
            $model,
            $trainingRepository->findTopResults($account, $runningSportId, 2),
            $vo2maxShape,
            $basicEndurance,
            $useMarathonShape
        )->getPrognosisObject();

        $personalBests = $raceResultRepository->findPersonalBestDetailsForDistances($account, $runningSportId, $distances);
        $this->numberOfPersonalBests = count($personalBests);

        foreach ($distances as $distance) {
            $distanceInKm = (float) $distance;
            $personalBest = $personalBests[sprintf('%.2f', $distanceInKm)] ?? null;

            $this->rows[] = [
                'distance' => $distanceInKm,
                'pbSeconds' => $personalBest['seconds'] ?? null,
                'pbActivityId' => $personalBest['activity_id'] ?? null,
                'prognosisSeconds' => $this->prognosis->areValuesValid() ? $this->prognosis->getSeconds($distanceInKm) : 0.0,
            ];
        }
    }

    public function hasValidPrognosis(): bool
    {
        return $this->prognosis->areValuesValid();
    }

    public function numberOfPersonalBests(): int
    {
        return $this->numberOfPersonalBests;
    }

    /**
     * @return array<int, array{distance: float, pbSeconds: float|null, pbActivityId: int|null, prognosisSeconds: float}>
     */
    public function rows(): array
    {
        return $this->rows;
    }
}
