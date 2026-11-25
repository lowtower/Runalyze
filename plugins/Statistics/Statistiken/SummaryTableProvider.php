<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Statistiken;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Dataset;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\DefaultConfiguration;
use Runalyze\Dataset\Keys;
use Runalyze\Dataset\Keys\AbstractKey;
use Runalyze\Dataset\Keys\Distance;
use Runalyze\Model\Sport\Entity as DatasetSport;
use Runalyze\Profile\View\DatasetPrivacyProfile;
use Runalyze\Util\Time;

final readonly class SummaryTableProvider
{
    public function __construct(
        private DatasetSummaryRepository $summaries,
        private EntityManagerInterface $entityManager,
        private SportRepository $sportRepository,
        private TrainingRepository $trainingRepository,
        private StatisticsFilterFactory $filters,
        private ConfigurationManager $configurationManager
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function years(Account $account, ?int $sportId, bool $compare = false, bool $public = false): array
    {
        if ($account->getId() === null) {
            throw new LogicException('Summary statistics require a persisted account.');
        }

        $today = $this->filters->todayFor($account);
        $currentYear = (int) $today->format('Y');
        $firstActivity = $this->trainingRepository->getStartTime($account);
        $firstYear = $firstActivity === null ? $currentYear : min($currentYear, (int) gmdate('Y', $firstActivity));

        return $this->table(
            $account,
            $sportId,
            $today->setDate($firstYear, 1, 1),
            $today->setDate($currentYear + 1, 1, 1),
            'year',
            __('All years'),
            $compare,
            $public,
            $firstActivity
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function table(
        Account $account,
        ?int $sportId,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        string $period,
        string $title,
        bool $compare,
        bool $public = false,
        ?int $firstActivity = null
    ): array {
        $accountId = $account->getId();
        if ($accountId === null) {
            throw new LogicException('Summary statistics require a persisted account.');
        }

        $sport = $sportId === null ? null : $this->sportRepository->findOneBy([
            'id' => $sportId,
            'account' => $account,
        ]);
        $missingSport = $sportId !== null && $sport === null;
        $sportId = $sport?->getId();
        $configuration = $this->configurationManager->getList($account);
        $runningAverages = $period === 'year' && $sportId === $configuration->getGeneral()->getRunningSport();
        $columns = $this->columns($account, $public);

        $dates = [];
        for ($date = $from; $date < $until; $date = $date->modify('+1 ' . $period)) {
            $dates[] = $date;
        }

        $starts = array_map(static fn (DateTimeImmutable $date): int => $date->getTimestamp(), $dates);
        $data = $starts === [] || $missingSport ? [] : $this->summaries->summarize(
            $account,
            $sportId,
            $starts,
            $until->getTimestamp(),
            array_column($columns, 'key'),
            $configuration->getVO2max()->useCorrectionForElevation()
        );

        $datasetSport = $sport instanceof Sport ? new DatasetSport([
            'id' => $sportId,
            DatasetSport::NAME => $sport->getName(),
            DatasetSport::HAS_DISTANCES => $sport->getDistances(),
            DatasetSport::PACE_UNIT => $sport->getSpeed(),
            DatasetSport::INTERNAL_SPORT_ID => $sport->getInternalSportId(),
        ]) : null;
        $today = $runningAverages ? $this->filters->todayFor($account) : null;
        $sundayStart = (int) $configuration->get('general.WEEK_START') !== 1;
        $rows = [];
        foreach (array_reverse($dates, true) as $index => $date) {
            $start = $starts[$index];
            $end = ($starts[$index + 1] ?? $until->getTimestamp()) - 1;
            $row = $data[$start] ?? [];
            $cells = [];
            $averages = null;
            if ($row !== []) {
                if ($compare) {
                    $row[Distance::KEY_DISTANCE_COMPARISON] = (float) ($data[$starts[$index - 1] ?? 0]['distance'] ?? 0.0);
                }

                $row['sportid'] = $sportId ?? 0;
                $context = new Context($row, $accountId, $datasetSport);
                foreach ($columns as ['key' => $key, 'style' => $style]) {
                    $extra = $key->valueDependendCssStyle($context);
                    $cells[] = [
                        'class' => trim($key->cssClass() . ' ' . ($extra['class'] ?? '')),
                        'style' => $style . ($extra['style'] ?? ''),
                        'html' => $key->stringFor($context),
                    ];
                }

                if ($today instanceof DateTimeImmutable) {
                    $averages = $this->runningAverages($date, $today, $firstActivity, (float) $row['distance']);
                }
            }

            $rows[] = [
                'start' => $start,
                'end' => $end,
                'label' => match ($period) {
                    'year' => $date->format('Y'),
                    'month' => Time::month($date->format('m')),
                    default => __('Week') . ' ' . (int) ($sundayStart ? $date->modify('+1 day') : $date)->format('W'),
                },
                'range' => $period === 'week' ? $date->format('d.m') . ' - ' . gmdate('d.m', $end) : '',
                'count' => (int) ($row['num'] ?? 0),
                'averages' => $averages,
                'cells' => $cells,
            ];
        }

        return [
            'title' => $title,
            'period' => $period,
            'sport' => $sportId,
            'publicUsername' => $public ? $account->getUsername() : null,
            'distanceUnit' => $configuration->getUnitSystem()->getDistanceUnit(),
            'hasRunningAverages' => $runningAverages,
            'colspan' => count($columns) + 2 + (int) $runningAverages,
            'rows' => $rows,
        ];
    }

    /**
     * @return list<array{key: AbstractKey, style: string}>
     */
    private function columns(Account $account, bool $public): array
    {
        $dataset = $this->entityManager->getRepository(Dataset::class)->findAllFor($account);
        $definitions = [];
        foreach ($dataset as $entry) {
            $definitions[$entry->getKeyId()] = [
                'active' => $entry->getActive(),
                'style' => $entry->getStyle(),
                'privacy' => $entry->getPrivacy(),
            ];
        }

        $columns = [];
        foreach ($definitions ?: new DefaultConfiguration()->data() as $id => $definition) {
            if (! $definition['active'] || ($public && (int) $definition['privacy'] === DatasetPrivacyProfile::PRIVATE_KEY)) {
                continue;
            }

            $key = Keys::get($id);
            if ($key->isShownInSummary()) {
                $columns[] = [
                    'key' => $key,
                    'style' => $definition['style'],
                ];
            }
        }

        return $columns;
    }

    /**
     * @return array{weekly: float, monthly: float}
     */
    private function runningAverages(DateTimeImmutable $year, DateTimeImmutable $today, ?int $firstActivity, float $distance): array
    {
        $first = $year;
        $end = $year->modify('+1 year');
        $monthFactor = 12.0;
        if ($year->format('Y') === $today->format('Y')) {
            $end = $today->modify('+1 day');
            $monthFactor = ($end->getTimestamp() - $first->getTimestamp()) / 86400 / 30.4;
        } elseif ($firstActivity !== null && gmdate('Y', $firstActivity) === $year->format('Y')) {
            $first = new DateTimeImmutable('@' . $firstActivity)->setTime(0, 0);
            $monthFactor = ($end->getTimestamp() - $first->getTimestamp()) / 86400 / 30.4;
        }

        $days = ($end->getTimestamp() - $first->getTimestamp()) / 86400;

        return [
            'weekly' => $distance * 7 / $days,
            'monthly' => $distance / $monthFactor,
        ];
    }
}
