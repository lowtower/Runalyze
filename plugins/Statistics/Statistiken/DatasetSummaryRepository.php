<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Statistiken;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Dataset\Keys\AbstractKey;
use Runalyze\Dataset\Keys\Pace;
use Runalyze\Dataset\SummaryMode;

final readonly class DatasetSummaryRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @param non-empty-list<int> $starts
     * @param list<AbstractKey> $keys
     * @return array<int, array<string, mixed>> Summaries indexed by the first timestamp of each period.
     */
    public function summarize(Account $account, ?int $sportId, array $starts, int $until, array $keys, bool $elevationCorrection): array
    {
        $connection = $this->entityManager->getConnection();
        $table = $connection->quoteSingleIdentifier($this->entityManager->getClassMetadata(Training::class)->getTableName());
        $values = [
            'account' => $account->getId(),
            'from' => $starts[0],
            'until' => $until,
        ];
        $periods = [];
        foreach ($starts as $index => $start) {
            $periods[] = 'WHEN time < :end' . $index . ' THEN :start' . $index;
            $values['start' . $index] = $start;
            $values['end' . $index] = $starts[$index + 1] ?? $until;
        }

        $columns = [
            'distance' => SummaryMode::query(SummaryMode::SUM, 'distance'),
            's' => SummaryMode::query(SummaryMode::SUM, 's'),
        ];
        foreach ($keys as $key) {
            if ($key->isInDatabase()) {
                $column = $key->column();
                $columns[$column] ??= SummaryMode::query($key->summaryMode(), $column, $elevationCorrection);
            }
        }

        $sql = 'SELECT CASE ' . implode(' ', $periods) . ' END AS period_start, COUNT(*) AS num, '
            . 'SUM(CASE WHEN distance > 0 THEN s ELSE 0 END) AS ' . Pace::DURATION_SUM_WITH_DISTANCE_KEY . ', '
            . implode(', ', $columns)
            . ' FROM ' . $table . ' WHERE accountid = :account AND time >= :from AND time < :until';
        if ($sportId !== null) {
            $sql .= ' AND sportid = :sport';
            $values['sport'] = $sportId;
        }

        $rows = $connection->fetchAllAssociative($sql . ' GROUP BY period_start', $values, array_fill_keys(array_keys($values), ParameterType::INTEGER));

        return array_column($rows, null, 'period_start');
    }
}
