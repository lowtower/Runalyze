<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class StatisticsFilterFactory
{
    private const int ALL = -1;

    public function __construct(
        private ClockInterface $clock,
        private SportRepository $sportRepository,
        private TrainingRepository $trainingRepository
    ) {}

    /**
     * @return array{sport: int, year: int, sports: array<int, string>, years: array<int, string>, from: ?int, until: ?int}
     */
    public function fromQuery(
        Account $account,
        InputBag $query,
        int $defaultSport = self::ALL,
        ?int $defaultYear = null
    ): array {
        $sportParameter = $query->getString('sport');
        $yearParameter = $query->getString('jahr');
        $sport = is_numeric($sportParameter) ? (int) $sportParameter : $defaultSport;

        $sports = [
            self::ALL => __('All'),
        ];
        foreach ($this->sportRepository->findAllFor($account) as $availableSport) {
            $sports[$availableSport->getId()] = $availableSport->getName();
        }

        if (! isset($sports[$sport])) {
            $sport = self::ALL;
        }

        // Activity timestamps represent local calendar dates in UTC, without an offset.
        $timezone = $account->getTimezone();
        $timezoneName = Timezone::isValidValue($timezone) ? Timezone::getFullNameByEnum($timezone) : date_default_timezone_get();
        $localDate = $this->clock->now()
            ->setTimezone(new DateTimeZone($timezoneName))
            ->format('Y-m-d');
        $today = new DateTimeImmutable($localDate, new DateTimeZone('UTC'));
        $currentYear = (int) $today->format('Y');
        $firstActivity = $this->trainingRepository->getStartTime($account);
        $firstYear = $firstActivity === null ? $currentYear : min($currentYear, (int) gmdate('Y', $firstActivity));

        $years = [
            self::ALL => __('All years'),
            6 => __('Last 6 months'),
            12 => __('Last 12 months'),
        ];
        for ($availableYear = $currentYear; $availableYear >= $firstYear; --$availableYear) {
            $years[$availableYear] = (string) $availableYear;
        }

        $defaultYear ??= $currentYear;
        $year = is_numeric($yearParameter) ? (int) $yearParameter : $defaultYear;
        if (! isset($years[$year]) && ($year < 1000 || $year > 9999)) {
            $year = $defaultYear;
        }

        $from = null;
        $until = null;
        if ($year === 6 || $year === 12) {
            // The legacy rolling windows exclude the first month's exact midnight.
            $from = $today->modify(sprintf('first day of -%d months', $year - 1))->getTimestamp() + 1;
        } elseif ($year !== self::ALL) {
            $from = $today->setDate($year, 1, 1)->getTimestamp();
            $until = $today->setDate($year + 1, 1, 1)->getTimestamp();
        }

        return [
            'sport' => $sport,
            'year' => $year,
            'sports' => $sports,
            'years' => $years,
            'from' => $from,
            'until' => $until,
        ];
    }
}
