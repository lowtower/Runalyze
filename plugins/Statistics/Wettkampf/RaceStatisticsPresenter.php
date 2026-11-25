<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Wettkampf;

use DataBrowserLinker;
use Plot;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Activity\Temperature;
use Runalyze\AgeGrade\Lookup;
use Runalyze\AgeGrade\Table\FemaleTable;
use Runalyze\AgeGrade\Table\MaleTable;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRequestHandlerInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Data\Weather\Condition;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Profile\Athlete\Gender;
use Runalyze\View\RaceResult\Dataview;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @phpstan-import-type RaceStatisticsRow from RaceresultRepository
 */
final readonly class RaceStatisticsPresenter implements ModernStatisticsInterface, ModernStatisticsConfigurationInterface, ModernStatisticsRequestHandlerInterface
{
    public const string KEY = 'RunalyzePluginStat_Wettkampf';

    public function __construct(
        private StatisticsFilterFactory $filters,
        private SportRepository $sports,
        private RaceresultRepository $races,
        private ConfigurationManager $configurationManager,
        private RaceStatisticsSettings $settings
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Race results');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Personal bests and everything else related to your competitions.');
    }

    public function getTemplate(): string
    {
        return '@Wettkampf/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Wettkampf/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        $settings = $this->settings->forInstallation($account, $installation);
        $units = $this->configurationManager->getList($account)->getGeneral()->getDistanceUnitSystem();
        $factor = $units->distanceToPreferredUnitFactor();

        return [
            'mainDistance' => (string) round($settings['mainDistance'] * $factor, 2),
            'distances' => implode(', ', array_map(static fn (float $km): string => (string) round($km * $factor, 2), $settings['distances'])),
            'unit' => $units->distanceUnit(),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $factor = $this->configurationManager->getList($account)->getGeneral()->getDistanceUnitSystem()->distanceToKmFactor();
        $this->settings->update($account, $installation, $data, $factor);
    }

    public function handleRequest(Account $account, Plugin $installation, InputBag $query): void
    {
        $selection = $query->all()['dat'] ?? null;
        if ($selection !== null && $selection !== '') {
            $this->settings->updateFunRace($account, $installation, $selection);
        }
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $configuration = $this->configurationManager->getList($account);
        $general = $configuration->getGeneral();
        $units = $general->getDistanceUnitSystem();
        $settings = $this->settings->forInstallation($account, $installation);

        $sports = [];
        foreach ($this->sports->findAllFor($account) as $sport) {
            $sports[$sport->getId()] = $sport;
        }

        $requestedSport = filter_var($query->all()['sport'] ?? null, FILTER_VALIDATE_INT);
        $sport = $sports[(int) $requestedSport] ?? $sports[$general->getMainSport()] ?? null;
        $sportId = $sport?->getId();
        $races = $sportId === null ? [] : $this->races->findForStatistics($account, $sportId);
        $birthYear = $account->getBirthyear();
        $gender = $account->getGender();
        $isRunning = $sportId === $general->getRunningSport();
        $currentYear = (int) $this->filters->todayFor($account)->format('Y');
        $ageGrade = $isRunning && $birthYear !== null && $birthYear < $currentYear && in_array($gender, [Gender::MALE, Gender::FEMALE], true)
            ? new Lookup($gender === Gender::FEMALE ? new FemaleTable() : new MaleTable(), $currentYear - $birthYear)
            : null;
        $paceUnit = new LegacyUnitConverter()->getLegacyPaceUnit($sport?->getSpeed(), true);

        $funIds = array_fill_keys($settings['funIds'], true);
        $rows = [];
        $groups = [];
        foreach ($races as $race) {
            $id = $race['id'];
            $rows[$id] = $this->raceRow($race, $configuration, $paceUnit, $ageGrade, $currentYear, isset($funIds[$id]));
            $groups[(string) ($race['officialDistance'] ?? 0)][] = $race;
        }

        ksort($groups, SORT_NUMERIC);

        $bests = [];
        $plots = [];
        foreach ($groups as $distance => $group) {
            $distance = (float) $distance;
            if (count($group) < 2 && ! in_array($distance, $settings['distances'], true)) {
                continue;
            }

            $best = $group[0];
            foreach ($group as $race) {
                if ($race['officialTime'] < $best['officialTime']) {
                    $best = $race;
                }
            }

            $bests[] = $rows[$best['id']];
            $plots[] = $this->trend($distance, $group, $funIds, $units);
        }

        $activePlot = in_array($settings['mainDistance'], array_column($plots, 'km'), true)
            ? $settings['mainDistance']
            : ($plots[0]['km'] ?? null);
        $weather = [];
        foreach ($this->races->countWeatherConditionsForStatistics($account) as $condition => $count) {
            $weather[] = [
                'count' => $count,
                'icon' => new Condition($condition)->icon()->code(),
            ];
        }

        return [
            'pluginId' => $installation->getId(),
            'sport' => $sport,
            'sports' => $sports,
            'races' => $rows,
            'bests' => $bests,
            'comparison' => RaceYearComparison::table($races, $settings['distances'], $units),
            'plots' => $plots,
            'activePlot' => $activePlot,
            'isRunning' => $isRunning,
            'hasAgeGrade' => $ageGrade instanceof \Runalyze\AgeGrade\Lookup,
            'weather' => $weather,
        ];
    }

    /**
     * @param RaceStatisticsRow $race
     * @return array<string, bool|int|string>
     */
    private function raceRow(
        array $race,
        RunalyzeConfigurationList $configuration,
        int $paceUnit,
        ?Lookup $ageGrade,
        int $currentYear,
        bool $fun
    ): array {
        $general = $configuration->getGeneral();
        $distance = new Distance($race['officialDistance'], $general->getDistanceUnitSystem());
        $duration = new Duration($race['officialTime']);
        $pace = new Pace($race['officialTime'], $race['officialDistance'], $paceUnit);
        $condition = new Condition($race['weatherid']);
        $temperature = new Temperature($race['temperature'], $general->getTemperatureUnit());
        $weatherIcon = $condition->icon();
        if ($race['isNight']) {
            $weatherIcon->setAsNight();
        }

        $grade = null;
        if ($ageGrade instanceof \Runalyze\AgeGrade\Lookup && $race['officialDistance'] >= $ageGrade->getMinimalDistance() && $race['officialTime'] >= 1) {
            $grade = $ageGrade->getAgeGrade($race['officialDistance'], (int) $race['officialTime'], $currentYear - (int) gmdate('Y', $race['time']));
        }

        return [
            'id' => $race['id'],
            'name' => $race['name'],
            'dateLink' => DataBrowserLinker::weekLink(gmdate('d.m.Y', $race['time']), $race['time']),
            'distance' => $race['officialDistance'] > 0
                ? ($race['isTrack'] ? $distance->stringMeter() : $distance->string(true, (int) $configuration->get('activity-view.TRAINING_DECIMALS')))
                : '',
            'time' => $duration->string(Duration::FORMAT_COMPETITION),
            'pace' => $pace->valueWithAppendix(),
            'heartRate' => ($race['pulseAvg'] ?: '?') . ' / ' . ($race['pulseMax'] ?: '?') . ' bpm',
            'weather' => $condition->isUnknown() && $temperature->isEmpty()
                ? '' : $weatherIcon->code() . ' ' . $condition->string() . ' ' . __('at') . ' ' . $temperature->string(),
            'overall' => Dataview::placementWithTooltip($race['placeTotal'], $race['participantsTotal'], __('of %u overall')),
            'ageGroup' => Dataview::placementWithTooltip($race['placeAgeclass'], $race['participantsAgeclass'], __('of %u in your age group')),
            'gender' => Dataview::placementWithTooltip($race['placeGender'], $race['participantsGender'], __('of %u men/women')),
            'fun' => $fun,
            'grade' => $grade === null ? '-' : number_format(100 * $grade->getPerformance(), 2) . ' %',
            'gradeTooltip' => $grade === null ? '' : __('Age standard') . ': ' . Duration::format($grade->getAgeStandard()) . ', '
                . __('Open standard') . ': ' . Duration::format($grade->getOpenStandard()) . ' '
                . sprintf(__('via tables by %s'), 'Alan Jones / WMA / USATF'),
        ];
    }

    /**
     * @param list<RaceStatisticsRow> $races
     * @param array<int, true> $funIds
     * @return array{km: float, name: string, id: string, javascript: string}
     */
    private function trend(float $distance, array $races, array $funIds, DistanceUnitSystem $units): array
    {
        $results = [];
        foreach ($races as $race) {
            if (! isset($funIds[$race['id']])) {
                $results[$race['time'] . '000'] = $race['officialTime'] * 1000;
            }
        }

        $name = new Distance($distance, $units)->stringAuto(true, 1);
        $id = 'bestzeit' . (int) round($distance * 1000);
        $plot = new Plot($id, 480, 190);
        $plot->Data[] = [
            'label' => str_replace('&nbsp;', ' ', sprintf(__('Result over %s'), $name)),
            'data' => $results,
        ];

        $plot->setMarginForGrid(5);
        $plot->setXAxisAsTime();
        if (count($results) === 1) {
            $plot->setXAxisTimeFormat('%d.%m.%y');
        }

        $plot->addYAxis(1, 'left');
        $plot->setYAxisAsTime(1);
        $plot->setYAxisTimeFormat($results !== [] && max($results) > 3600000 ? '%H:%M:%S' : '%M:%S', 1);
        $plot->smoothing(false);
        $plot->lineWithPoints();

        return [
            'km' => $distance,
            'name' => $name,
            'id' => $id,
            'javascript' => $plot->getJavaScript(),
        ];
    }
}
