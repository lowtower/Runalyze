<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Runalyze\Sports\Performance\Monotony;
use Symfony\Component\Clock\ClockInterface;

final readonly class RechenspieleTrimpDataProvider
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private ClockInterface $clock
    ) {}

    /**
     * Return daily TRIMP indexed by days relative to the account's current day.
     *
     * @return array<int, float>
     */
    public function getPerformanceModelData(Account $account): array
    {
        $today = $this->todayFor($account);
        $data = [];

        foreach ($this->trainingRepository->findDailyTrimpForAccount($account) as $row) {
            $day = new DateTimeImmutable($row['day'], new DateTimeZone('UTC'));
            $offset = (int) $today->diff($day)->format('%r%a');
            $data[$offset] = $row['trimp'];
        }

        return $data;
    }

    /**
     * @return list<float>
     */
    public function getMonotonyData(Account $account): array
    {
        $today = $this->todayFor($account);
        $firstDay = $today->modify('-' . (Monotony::DAYS - 1) . ' days');

        $rows = $this->trainingRepository->findDailyTrimpForAccount(
            $account,
            $firstDay->getTimestamp(),
            $today->setTime(23, 59, 59)->getTimestamp()
        );

        return array_column($rows, 'trimp');
    }

    private function todayFor(Account $account): DateTimeImmutable
    {
        $timezone = new DateTimeZone(TimezoneEnum::getFullNameByEnum($account->getTimezone()));
        $date = $this->clock->now()->setTimezone($timezone)->format('Y-m-d');

        return new DateTimeImmutable($date, new DateTimeZone('UTC'));
    }
}
