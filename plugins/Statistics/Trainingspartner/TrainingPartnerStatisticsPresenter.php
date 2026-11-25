<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Trainingspartner;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class TrainingPartnerStatisticsPresenter implements ModernStatisticsInterface
{
    public const string KEY = 'RunalyzePluginStat_Trainingspartner';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Training partner');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('How often did you train with whom?');
    }

    public function getTemplate(): string
    {
        return '@Trainingspartner/statistics.html.twig';
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $filter = $this->filterFactory->fromQuery($account, $query);
        $sport = $filter['sport'];
        $year = $filter['years'][$filter['year']] ?? (string) $filter['year'];
        $total = 0;
        $withPartner = 0;
        $counts = [];

        $activities = $this->trainingRepository->findTrainingPartnersFor(
            $account,
            $sport > 0 ? $sport : null,
            $filter['from'],
            $filter['until']
        );
        foreach ($activities as $partners) {
            ++$total;
            if ($partners === '') {
                continue;
            }

            ++$withPartner;
            foreach (explode(',', $partners) as $partner) {
                $name = trim($partner);
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts, SORT_NUMERIC);
        $rows = [];
        foreach ($counts as $name => $count) {
            $rows[$count][] = $name;
        }

        $summary = sprintf(__('You have trained <strong>%sx</strong> in total and out of that <strong>%sx</strong> with a training partner, '), $total, $withPartner)
            . sprintf(__('that are <strong>%s</strong> &#37;.'), $total === 0 ? 0 : round(100 * $withPartner / $total));

        return [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'title' => $this->getName() . ': ' . ($sport > 0 ? $filter['sports'][$sport] . ', ' : '') . $year,
            'yearLabel' => $year,
            'tableTitle' => __('All training partners'),
            'emptyMessage' => __('You have only trained on your own.'),
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}
