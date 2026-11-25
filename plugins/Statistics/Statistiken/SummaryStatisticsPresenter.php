<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Statistiken;

use DateTimeImmutable;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class SummaryStatisticsPresenter implements ModernStatisticsInterface, ModernStatisticsConfigurationInterface
{
    public const string KEY = 'RunalyzePluginStat_Statistiken';

    public function __construct(
        private StatisticsFilterFactory $filters,
        private SummaryTableProvider $tables,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager,
        private DoctrinePluginConfigurationStorage $configurationStorage
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Statistics');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Monthly and weekly summaries for all sports');
    }

    public function getTemplate(): string
    {
        return '@Statistiken/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Statistiken/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        $id = $this->installationId($account, $installation);
        $values = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];

        return [
            'compareWeeks' => ($values['compare_weeks'] ?? '1') === '1',
            'showStreak' => ($values['show_streak'] ?? '1') === '1',
            'minimumDays' => max(0, (int) ($values['show_streak_days'] ?? 10)),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->installationId($account, $installation);
        $values = [
            'compare_weeks' => isset($data['compare_weeks']) ? '1' : '0',
            'show_streak' => isset($data['show_streak']) ? '1' : '0',
        ];
        $days = $data['show_streak_days'] ?? null;
        if (is_string($days) && filter_var($days, FILTER_VALIDATE_INT) !== false) {
            $values['show_streak_days'] = (string) max(0, (int) $days);
        }

        $this->configurationStorage->saveValues($installation, $values);
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $settings = $this->getConfigurationData($account, $installation);
        $configuration = $this->configurationManager->getList($account);
        $general = $configuration->getGeneral();
        $filter = $this->filters->fromQuery($account, $query, $general->getMainSport(), 6);
        $filter['years'][-1] = __('Year on year');
        $today = $this->filters->todayFor($account);
        $currentYear = (int) $today->format('Y');
        $year = $filter['year'];
        $yearLabel = $filter['years'][$year] ?? (string) $year;
        $sport = $filter['sport'] > 0 ? $filter['sport'] : null;
        $allWeeks = $year >= 1000 && $query->getString('dat') === 'allWeeks';
        $mondayStart = (int) $configuration->get('general.WEEK_START') === 1;
        $currentWeek = $this->weekStart($today, $mondayStart);
        $compare = $settings['compareWeeks'];

        $overview = null;
        $streak = '';
        $weeks = null;
        if ($allWeeks) {
            $firstWeek = $this->weekStart($today->setDate($year, 1, 1), $mondayStart);
            $lastWeek = $year === $currentYear ? $currentWeek : $this->weekStart($today->setDate($year, 12, 31), $mondayStart);
            $weeks = $this->tables->table($account, $sport, $firstWeek, $lastWeek->modify('+1 week'), 'week', sprintf(__('All training weeks %s'), $year), $compare);
        } else {
            if ($year === -1) {
                $overview = $this->tables->years($account, $sport, $compare);
            } else {
                $calendarYear = $year >= 1000;
                $lastMonth = $today->modify('first day of next month');
                $from = $calendarYear ? $today->setDate($year, 1, 1) : $lastMonth->modify('-' . $year . ' months');
                $until = $calendarYear ? $today->setDate($year + 1, 1, 1) : $lastMonth;
                $overview = $this->tables->table($account, $sport, $from, $until, 'month', $yearLabel, $compare);
            }

            if ($settings['showStreak']) {
                $streak = $this->streak($account, $general->getRunningSport(), $today, $settings['minimumDays']);
            }

            if (in_array($year, [$currentYear, 6, 12], true)) {
                $weeks = $this->tables->table(
                    $account,
                    $sport,
                    $currentWeek->modify('-9 weeks'),
                    $currentWeek->modify('+1 week'),
                    'week',
                    __('Last 10 training weeks'),
                    $compare
                );
            }
        }

        return [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'yearLabel' => $yearLabel,
            'title' => $this->getName() . ': ' . ($sport === null ? '' : $filter['sports'][$sport] . ', ') . $yearLabel,
            'allWeeks' => $allWeeks,
            'weeksYear' => $year >= 1000 ? $year : $currentYear,
            'overview' => $overview,
            'streak' => $streak,
            'weeks' => $weeks,
        ];
    }

    private function weekStart(DateTimeImmutable $date, bool $mondayStart): DateTimeImmutable
    {
        $offset = ((int) $date->format('w') + ($mondayStart ? 6 : 0)) % 7;

        return $date->modify('-' . $offset . ' days');
    }

    private function streak(Account $account, int $sport, DateTimeImmutable $today, int $minimumDays): string
    {
        $expected = $today->getTimestamp();
        $days = 0;
        $last = null;
        $start = $expected;
        $prospective = false;
        foreach ($this->trainingRepository->iterateActivityDaysFor($account, $sport, $today->modify('+1 day')->getTimestamp()) as $day) {
            if ($last === null) {
                $last = $day;
                if ($day === $expected - 86400) {
                    $prospective = true;
                    ++$days;
                    $expected -= 86400;
                }
            }

            if ($day !== $expected) {
                break;
            }

            ++$days;
            $start = $day;
            $expected -= 86400;
        }

        if ($days < $minimumDays) {
            return '';
        }

        if ($days === 0) {
            return __("You don't have a streak. Go out and start one!")
                . ($last === null ? '' : ' ' . sprintf(__('Your last run was on %s'), gmdate('d.m.Y', $last)));
        }

        return ($prospective ? __('If you run today: ') : '')
            . sprintf(_n('%d day of running since %s', '%d days of running since %s', $days), $days, gmdate('d.m.Y', $start));
    }

    private function installationId(Account $account, Plugin $installation): int
    {
        $id = $installation->getId();
        if ($id === null || $account->getId() === null || $installation->getAccount()->getId() !== $account->getId()
            || $installation->getKey() !== self::KEY || ! $installation->isStatistic()
        ) {
            throw new InvalidArgumentException('The statistics installation is not available for this account.');
        }

        return $id;
    }
}
