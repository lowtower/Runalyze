<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisData;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class MonthlyStatsPresenter implements ModernStatisticsInterface
{
    public const string KEY = 'RunalyzePluginStat_MonthlyStats';

    public function __construct(
        private SportSelectionFactory $sportSelectionFactory,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Monthly Stats');
    }

    public function getDescription(?Account $account = null): string
    {
        $unit = $this->configurationManager->getList($account)->getGeneral()->getDistanceUnitSystem()->distanceUnit();

        return sprintf(__('How many %s/hours did you do per month'), $unit);
    }

    public function getTemplate(): string
    {
        return '@MonthlyStats/statistics.html.twig';
    }

    /**
     * @return array{pluginId: ?int, analysisData: AnalysisData}
     */
    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $sportSelection = $this->sportSelectionFactory->getSelection($query->getString('sport'), account: $account);
        $analysisSelection = new AnalysisSelection($query->get('dat'));

        if (! $analysisSelection->hasCurrentKey()) {
            $analysisSelection->setCurrentKey(AnalysisSelection::DISTANCE);
        }

        $analysisData = new AnalysisData($sportSelection, $analysisSelection, $this->trainingRepository, $account);
        $analysisData->setValueExtension(new ValueExtension($this->configurationManager, $account));

        return [
            'pluginId' => $installation->getId(),
            'analysisData' => $analysisData,
        ];
    }
}
