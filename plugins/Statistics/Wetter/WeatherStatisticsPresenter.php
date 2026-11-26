<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Wetter;

use DateTimeImmutable;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Runalyze\Data\Weather\Condition;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Util\Time;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class WeatherStatisticsPresenter implements StatisticsInterface, StatisticsConfigurationInterface, StatisticsRequestHandlerInterface
{
    public const string KEY = 'RunalyzePluginStat_Wetter';

    private const string SETTING = 'equipment_type';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private EquipmentTypeRepository $equipmentTypeRepository,
        private DoctrinePluginConfigurationStorage $configurationStorage,
        private ConfigurationManager $configurationManager
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Weather');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Statistics about weather conditions and temperatures.');
    }

    public function getTemplate(): string
    {
        return '@Wetter/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Wetter/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        $types = $this->equipmentTypes($account);

        return [
            'types' => $types,
            'selected' => $this->selectedType($account, $installation, $types),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $id = StatisticsInstallation::requireId($account, $installation, self::KEY);
        $requested = $data[self::SETTING] ?? null;

        if (is_string($requested) && ctype_digit($requested) && isset($this->equipmentTypes($account)[$requested])
            && $requested !== $this->storedType($id)
        ) {
            $this->configurationStorage->saveValue($installation, self::SETTING, $requested);
        }
    }

    public function handleRequest(Account $account, Plugin $installation, InputBag $query): void
    {
        if ($query->has('dat')) {
            $this->updateConfiguration($account, $installation, [
                self::SETTING => $query->all()['dat'],
            ]);
        }
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $types = $this->equipmentTypes($account);
        $selected = $this->selectedType($account, $installation, $types);
        $filter = $this->filterFactory->fromQuery($account, $query);
        $sport = $filter['sport'] > 0 ? $filter['sport'] : null;
        $from = $filter['from'];
        $until = $filter['until'];
        $year = $filter['year'];
        $yearLabel = $filter['years'][$year] ?? (string) $year;
        $firstMonth = 1;
        if ($from !== null && ($year === 6 || $year === 12)) {
            $start = new DateTimeImmutable('@' . ($from - 1));
            $firstMonth = (int) $start->format('n');
            $until = $start->modify('+' . $year . ' months')->getTimestamp();
        }

        $months = [];
        for ($index = 0; $index < 12; ++$index) {
            $month = ($firstMonth - 1 + $index) % 12 + 1;
            $months[$month] = Time::month((string) $month, true);
        }

        $configuration = $this->configurationManager->getList($account);
        $units = $configuration->getGeneral()->getDistanceUnitSystem();
        $unit = $configuration->getUnitSystem()->getTemperatureUnit();
        $extremes = $this->trainingRepository->findWeatherExtremesFor($account, $sport, $from, $until);
        foreach ($extremes['wind'] as &$row) {
            $row['formatted'] = html_entity_decode(new WindSpeed($row['value'], $units)->string(), ENT_QUOTES, 'UTF-8');
        }

        unset($row);

        [$temperatures, $conditions] = $this->monthlyWeather($account, $sport, $from, $until);
        [$equipment, $equipmentTemperatures] = $selected > 0
            ? $this->equipmentWeather($account, $selected, $sport, $from, $until)
            : [[], []];

        return [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'yearLabel' => $yearLabel,
            'title' => $this->getName() . ': ' . ($sport === null ? '' : $filter['sports'][$sport] . ', ') . $yearLabel,
            'types' => $types,
            'selected' => $selected,
            'extremes' => $extremes,
            'months' => $months,
            'temperatureUnit' => $unit->getAppendix(),
            'temperatures' => $temperatures,
            'conditions' => $conditions,
            'equipment' => $equipment,
            'equipmentTemperatures' => $equipmentTemperatures,
        ];
    }

    /**
     * @return array{array<int, float>, list<array{icon: string, counts: array<int, int>}>}
     */
    private function monthlyWeather(Account $account, ?int $sport, ?int $from, ?int $until): array
    {
        $sums = [];
        $samples = [];
        $counts = [];
        foreach ($this->trainingRepository->findMonthlyWeatherFor($account, $sport, $from, $until) as $row) {
            $month = (int) $row['month'];
            $counts[$row['condition']][$month] = (int) $row['count'];
            $sums[$month] = ($sums[$month] ?? 0.0) + (float) $row['temperatureSum'];
            $samples[$month] = ($samples[$month] ?? 0) + (int) $row['samples'];
        }

        $temperatures = [];
        foreach ($samples as $month => $count) {
            if ($count > 0) {
                $temperatures[$month] = round($sums[$month] / $count);
            }
        }

        $conditions = [];
        foreach (Condition::completeList() as $id) {
            if ($id !== WeatherConditionProfile::UNKNOWN) {
                $conditions[] = [
                    'icon' => new Condition($id)->icon()->code(),
                    'counts' => $counts[$id] ?? [],
                ];
            }
        }

        return [$temperatures, $conditions];
    }

    /**
     * @return array{array<int, array{name: string, counts: array<int, int>}>, list<array{name: string, minimum: int, maximum: int, average: float}>}
     */
    private function equipmentWeather(Account $account, int $type, ?int $sport, ?int $from, ?int $until): array
    {
        $equipment = [];
        $ranges = [];
        foreach ($this->trainingRepository->findMonthlyEquipmentWeatherFor($account, $type, $sport, $from, $until) as $row) {
            $id = $row['id'];
            $equipment[$id]['name'] = $row['name'];
            $equipment[$id]['counts'][(int) $row['month']] = (int) $row['count'];
            if ($row['minimum'] === null || $row['maximum'] === null) {
                continue;
            }

            $ranges[$id] = [
                'minimum' => min($ranges[$id]['minimum'] ?? $row['minimum'], $row['minimum']),
                'maximum' => max($ranges[$id]['maximum'] ?? $row['maximum'], $row['maximum']),
                'samples' => ($ranges[$id]['samples'] ?? 0) + (int) $row['samples'],
                'sum' => ($ranges[$id]['sum'] ?? 0.0) + (float) $row['temperatureSum'],
            ];
        }

        $summary = [];
        foreach ($ranges as $id => $row) {
            $summary[] = [
                'name' => $equipment[$id]['name'],
                'minimum' => $row['minimum'],
                'maximum' => $row['maximum'],
                'average' => round($row['sum'] / $row['samples']),
            ];
        }

        return [$equipment, $summary];
    }

    /**
     * @return array<int, string>
     */
    private function equipmentTypes(Account $account): array
    {
        $types = [
            0 => __('none'),
        ];
        foreach ($this->equipmentTypeRepository->findAllForPanel($account) as $type) {
            $types[$type->getId()] = $type->getName();
        }

        return $types;
    }

    /**
     * @param array<int, string> $types
     */
    private function selectedType(Account $account, Plugin $installation, array $types): int
    {
        $id = StatisticsInstallation::requireId($account, $installation, self::KEY);
        $stored = $this->storedType($id);

        return isset($types[$stored]) ? (int) $stored : 0;
    }

    private function storedType(int $id): string
    {
        return $this->configurationStorage->loadForPlugins([$id])[$id][self::SETTING] ?? '0';
    }
}
