<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroups;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Metrics\Common\UnitInterface;

class AnovaDataQuery
{
    protected \Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroupInterface $QueryGroup;

    protected QueryValueInterface $QueryValue;

    protected array $Groups = [];

    public function __construct(
        protected AnovaData $AnovaData
    ) {
        $this->QueryGroup = QueryGroups::getGroup($this->AnovaData->getValueToGroupBy());
        $this->QueryValue = QueryValues::get($this->AnovaData->getValueToLookAt());
    }

    public function loadAllGroups(EntityManager $entityManager, Account $account): void
    {
        $this->Groups = [];
        $groups = $this->QueryGroup->loadAllGroups($entityManager, $account, $this->AnovaData);

        foreach ($groups as $id => $label) {
            $this->Groups[(int) $id] = [
                'label' => $label,
                'data' => [],
            ];
        }
    }

    public function getValueUnit(UnitSystem $unitSystem): UnitInterface
    {
        $sports = $this->AnovaData->getSport();

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
            $this->Groups[(int) $row['grouping']]['data'][] = $unit->fromBaseUnit((float) $row['value']);
        }

        $this->filterEmptyGroups();

        return array_values($this->Groups);
    }

    protected function buildQuery(TrainingRepository $trainingRepository, Account $account): \Doctrine\ORM\Query
    {
        $queryBuilder = $trainingRepository->createQueryBuilder('t')
            ->select('1')
            ->andWhere('t.account = :account')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->join('t.sport', 's')
            ->setParameter('account', $account->getId())
            ->setParameter('startTime', $this->AnovaData->getDateFromTimestamp())
            ->setParameter('endTime', $this->AnovaData->getDateToTimestamp());

        $this->addSportConditionToQuery($queryBuilder);
        $this->addTypeConditionToQuery($queryBuilder);
        $this->QueryGroup->addSelectionToQuery($queryBuilder, 't', 'grouping', 's');
        $this->QueryValue->addSelectionToQuery($queryBuilder, 't', 'value');

        return $queryBuilder->getQuery();
    }

    protected function addSportConditionToQuery(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->in('t.sport', ':sports'));
        $queryBuilder->setParameter(':sports', array_map(fn (Sport $sport): ?int => $sport->getId(), $this->AnovaData->getSport()));
    }

    protected function addTypeConditionToQuery(QueryBuilder $queryBuilder)
    {
        $types = $this->AnovaData->getType();

        if ($types !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('t.type', ':types'));
            $queryBuilder->setParameter(':types', array_map(fn (Type $type): ?int => $type->getId(), $types));
        }
    }

    protected function filterEmptyGroups()
    {
        if (! $this->QueryGroup->showEmptyGroups()) {
            foreach ($this->Groups as $key => $data) {
                if (empty($data['data'])) {
                    unset($this->Groups[$key]);
                }
            }
        }
    }
}
