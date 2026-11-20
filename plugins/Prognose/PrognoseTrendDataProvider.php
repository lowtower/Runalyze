<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Calculation\BasicEndurance;

final readonly class PrognoseTrendDataProvider
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager,
        private PrognoseTrendSeriesCalculator $calculator
    ) {}

    /**
     * @return array{
     *     has_history: bool,
     *     prognosis: array<string, float>,
     *     prognosis_with_basic_endurance: array<string, float>
     * }
     */
    public function getSeries(Account $account, int $sportId, float $distance): array
    {
        $configuration = $this->configurationManager->getList($account);
        $history = $this->trainingRepository->findPrognosisTrendHistory(
            $account,
            $sportId,
            $configuration->useVO2maxCorrectionForElevation()
        );

        if ($history === []) {
            return [
                'has_history' => false,
                'prognosis' => [],
                'prognosis_with_basic_endurance' => [],
            ];
        }

        $settings = $configuration->getBasicEndurance();
        $endurance = new BasicEndurance();
        $endurance->setMinimalDistanceForLongjogs((float) $settings->getMinimalDistanceForLongJog());
        $endurance->setDaysToRecognizeForWeekKilometer($settings->getDaysToConsiderForWeeklyMileage());
        $endurance->setMinimalDaysToRecognizeForWeekKilometer((int) $configuration->get('basic-endurance.BE_DAYS_FOR_WEEK_KM_MIN'));
        $endurance->setDaysToRecognizeForLongjogs($settings->getDaysToConsiderForLongJogs());
        $endurance->setPercentageForWeekKilometer($settings->getPercentageForWeeklyMileage());

        $series = $this->calculator->calculate(
            $history,
            $distance,
            $endurance,
            $configuration->getVO2maxCorrectionFactor(),
            $configuration->getVO2max()->getNumberOfDaysToConsider(),
            date('Y-m-d')
        );

        return [
            'has_history' => true,
            'prognosis' => $series['prognosis'],
            'prognosis_with_basic_endurance' => $series['prognosis_with_basic_endurance'],
        ];
    }
}
