<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Metrics\Common\UnitInterface;

class TrendAnalysisDataQuery
{
    protected QueryValueInterface $QueryValue;

    protected array $Values = [];

    public function __construct(
        protected TrendAnalysisData $TrendAnalysisData
    ) {
        $this->QueryValue = QueryValues::get($this->TrendAnalysisData->getValueToLookAt());
    }

    public function getValueUnit(UnitSystem $unitSystem): UnitInterface
    {
        $sports = $this->TrendAnalysisData->getSport();

        if (count($sports) === 1) {
            $unitSystem->setPaceUnitFromSport(array_shift($sports));
        }

        return $this->QueryValue->getValueUnit($unitSystem);
    }

    public function getResults(TrainingRepository $trainingRepository, Account $account, UnitSystem $unitSystem): array
    {
        $unit = $this->getValueUnit($unitSystem);
        $iterator = $this->buildQuery($trainingRepository, $account)
            ->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        foreach ($iterator as $row) {
            $this->Values[(int) $row['time']] = $unit->fromBaseUnit((float) $row['value']);
        }

        return $this->Values;
    }

    protected function buildQuery(TrainingRepository $trainingRepository, Account $account): \Doctrine\ORM\Query
    {
        $queryBuilder = $trainingRepository->createQueryBuilder('t')
            ->select('t.time')
            ->andWhere('t.account = :account')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->join('t.sport', 's')
            ->setParameter('account', $account->getId())
            ->setParameter('startTime', $this->TrendAnalysisData->getDateFromTimestamp())
            ->setParameter('endTime', $this->TrendAnalysisData->getDateToTimestamp());

        $this->addSportConditionToQuery($queryBuilder);
        $this->addTypeConditionToQuery($queryBuilder);
        $this->QueryValue->addSelectionToQuery($queryBuilder, 't', 'value');

        return $queryBuilder->getQuery();
    }

    protected function addSportConditionToQuery(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->in('t.sport', ':sports'));
        $queryBuilder->setParameter(':sports', array_map(fn (Sport $sport): ?int => $sport->getId(), $this->TrendAnalysisData->getSport()));
    }

    protected function addTypeConditionToQuery(QueryBuilder $queryBuilder)
    {
        $types = $this->TrendAnalysisData->getType();

        if ($types !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('t.type', ':types'));
            $queryBuilder->setParameter(':types', array_map(fn (Type $type): ?int => $type->getId(), $types));
        }
    }
}
