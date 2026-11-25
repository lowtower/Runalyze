<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Wetter;

use Plot;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;
use Runalyze\Util\Time;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class TemperatureWindowPresenter
{
    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Account $account, InputBag $query): array
    {
        $requested = $query->getString('y');
        $period = $this->filterFactory->periodFromQuery($account, new InputBag([
            'jahr' => preg_match('/^[1-9][0-9]{3}$/D', $requested) ? $requested : '',
        ]));
        $years = array_filter($period['years'], static fn (int $year): bool => $year >= 1000, ARRAY_FILTER_USE_KEY);
        $year = $period['year'];
        $from = $period['from'];
        $until = $period['until'];
        assert($from !== null && $until !== null);
        $unit = $this->configurationManager->getList($account)->getUnitSystem()->getTemperatureUnit();

        $average = $this->averagePlot($account, $unit, $years);
        $daily = $this->createPlot('year' . $year, $unit, 0);
        $daily->setXAxisAsTime();
        $daily->Options['xaxis']['min'] = $from * 1000;
        $daily->Options['xaxis']['max'] = $until * 1000 - 1;
        $daily->showPoints(2);

        $points = [];
        foreach ($this->trainingRepository->findAverageTemperaturesByDayFor($account, $from, $until) as $row) {
            $points[(int) $row['dayStart'] * 1000] = round($unit->fromBaseUnit((float) $row['temperature']), 1);
        }

        if ($points === []) {
            $daily->raiseError(__('No data available.'));
        } else {
            $daily->Data[] = [
                'label' => __('Temperatures') . ' ' . $year,
                'data' => $points,
            ];
        }

        return [
            'year' => $year,
            'years' => $years,
            'plots' => [
                [
                    'div' => $average->getDiv(),
                    'script' => $average->getJavaScript(true),
                ],
                [
                    'div' => $daily->getDiv(),
                    'script' => $daily->getJavaScript(true),
                ],
            ],
        ];
    }

    /**
     * @param array<int, string> $years
     */
    private function averagePlot(Account $account, AbstractTemperatureUnit $unit, array $years): Plot
    {
        $plot = $this->createPlot('average', $unit, 1);
        $ticks = [];
        for ($i = 0; $i < 12; ++$i) {
            $ticks[] = [$i, Time::month((string) ($i + 1), true)];
        }

        $plot->setXLabels($ticks);
        $plot->lineWithPoints();

        $months = [];
        foreach ($this->trainingRepository->findAverageTemperaturesByMonthFor($account) as $row) {
            $year = (int) $row['year'];
            if (isset($years[$year])) {
                $months[$year] ??= array_fill(0, 12, null);
                $months[$year][(int) $row['month'] - 1] = round($unit->fromBaseUnit((float) $row['temperature']), 1);
            }
        }

        foreach ($months as $year => $temperatures) {
            $plot->Data[] = [
                'label' => $year,
                'data' => $temperatures,
            ];
        }

        if ($months === []) {
            $plot->raiseError(__('No data available.'));
        }

        return $plot;
    }

    private function createPlot(string $id, AbstractTemperatureUnit $unit, int $decimals): Plot
    {
        $plot = new Plot($id, 780, 240);
        $plot->setMarginForGrid(5);
        $plot->addYAxis(1, 'left');
        $plot->addYUnit(1, $unit->getAppendix(), $decimals);
        $plot->setYTicks(1, 5, false);

        $freezing = $unit->fromBaseUnit(0);
        $plot->addThreshold('y', $freezing);
        $plot->addMarkingArea('y', $unit->fromBaseUnit(-99), $freezing);
        $plot->smoothing(false);

        return $plot;
    }
}
