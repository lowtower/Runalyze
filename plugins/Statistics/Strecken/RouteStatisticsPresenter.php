<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Strecken;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInstallation;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class RouteStatisticsPresenter implements ModernStatisticsInterface, ModernStatisticsConfigurationInterface
{
    public const string KEY = 'RunalyzePluginStat_Strecken';

    private const string SHOW_CITIES = 'analyze_cities';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private DoctrinePluginConfigurationStorage $configurationStorage
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Routes');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Some statistics for your most frequent routes.');
    }

    public function getTemplate(): string
    {
        return '@Strecken/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Strecken/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        return [
            'showCities' => $this->showCities(StatisticsInstallation::requireId($account, $installation, self::KEY)),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        StatisticsInstallation::requireId($account, $installation, self::KEY);
        $this->configurationStorage->saveValue($installation, self::SHOW_CITIES, isset($data[self::SHOW_CITIES]) ? '1' : '0');
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $id = StatisticsInstallation::requireId($account, $installation, self::KEY);
        $showCities = $this->showCities($id);
        $filter = $this->filterFactory->fromQuery($account, $query);
        $sport = $filter['sport'] > 0 ? $filter['sport'] : null;
        $year = $filter['years'][$filter['year']] ?? (string) $filter['year'];
        $routes = $this->trainingRepository->findFrequentRoutesFor($account, $sport, $filter['from'], $filter['until']);

        $cities = [];
        $rare = [];
        if ($showCities) {
            $cities = $this->countCities($this->trainingRepository->iterateVisitedRouteCitiesFor($account, $sport, $filter['from'], $filter['until']));
            foreach ($cities as $city => $count) {
                if ($count <= 4) {
                    $rare[$count][] = (string) $city;
                }
            }

            ksort($rare, SORT_NUMERIC);
        }

        return [
            'pluginId' => $id,
            'filter' => $filter,
            'yearLabel' => $year,
            'title' => $this->getName() . ': ' . ($sport === null ? '' : $filter['sports'][$sport] . ', ') . $year,
            'routes' => $routes,
            'showCities' => $showCities,
            'frequentCities' => array_slice($cities, 0, 10, true),
            'rareCities' => $rare,
            'cityCount' => count($cities),
        ];
    }

    /**
     * @param iterable<string> $routeCities
     * @return array<string, int>
     */
    private function countCities(iterable $routeCities): array
    {
        $counts = [];
        foreach ($routeCities as $list) {
            foreach (explode(' - ', $list) as $city) {
                $city = trim($city);
                if ($city !== '') {
                    $counts[$city] = ($counts[$city] ?? 0) + 1;
                }
            }
        }

        ksort($counts, SORT_NATURAL | SORT_FLAG_CASE);
        arsort($counts, SORT_NUMERIC);

        return $counts;
    }

    private function showCities(int $id): bool
    {
        return ($this->configurationStorage->loadForPlugins([$id])[$id][self::SHOW_CITIES] ?? '1') === '1';
    }
}
