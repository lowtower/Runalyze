<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\EquipmentType as EquipmentTypeQueryGroup;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class EquipmentTypeQueryGroupTest extends TestCase
{
    public function testConstructorCoercesWellFormedNumericStringToInteger(): void
    {
        $queryGroup = new EquipmentTypeQueryGroup('42');

        $reflection = new \ReflectionObject($queryGroup);
        $property = $reflection->getProperty('EquipmentTypeId');

        $this->assertSame(42, $property->getValue($queryGroup));
    }

    public function testConstructorAcceptsARealIntegerDirectly(): void
    {
        $queryGroup = new EquipmentTypeQueryGroup(7);

        $reflection = new \ReflectionObject($queryGroup);
        $property = $reflection->getProperty('EquipmentTypeId');

        $this->assertSame(7, $property->getValue($queryGroup));
    }

    public function testConstructorThrowsTypeErrorForNonNumericStringId(): void
    {
        $this->expectException(\TypeError::class);

        new EquipmentTypeQueryGroup('not-a-number');
    }

    public function testAddSelectionToQueryBuildsExpectedJoinWhereAndDistinct(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('eq.id as et')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('tr.equipment', 'eq')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('eq.type = :equipmentTypeId')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with(':equipmentTypeId', 5)
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('distinct')
            ->willReturn($queryBuilder);

        (new EquipmentTypeQueryGroup(5))->addSelectionToQuery($queryBuilder, 'tr', 'et', 'sp');
    }

    public function testAddSelectionToQuerySelectAliasIsIndependentOfMainAlias(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('addSelect')
            ->willReturn($queryBuilder);
        $queryBuilder->method('leftJoin')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')
            ->willReturn($queryBuilder);
        $queryBuilder->method('distinct')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('eq.id as equipment_col');
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('training.equipment', 'eq');

        (new EquipmentTypeQueryGroup(5))->addSelectionToQuery($queryBuilder, 'training', 'equipment_col', 'sp');
    }

    public function testShowEmptyGroupsReturnsFalse(): void
    {
        $this->assertFalse((new EquipmentTypeQueryGroup(1))->showEmptyGroups());
    }

    public function testLoadAllGroupsReturnsIdToNameMapOfMatchingEquipment(): void
    {
        $shoe1 = $this->createMock(Equipment::class);
        $shoe1->method('getId')
            ->willReturn(101);
        $shoe1->method('getName')
            ->willReturn('Trail Shoes');

        $shoe2 = $this->createMock(Equipment::class);
        $shoe2->method('getId')
            ->willReturn(102);
        $shoe2->method('getName')
            ->willReturn('Road Shoes');

        $account = $this->createStub(Account::class);

        $equipmentRepository = $this->createMock(EquipmentRepository::class);
        $equipmentRepository->expects($this->once())
            ->method('findForTypeId')
            ->with(5, $account)
            ->willReturn([$shoe1, $shoe2]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->with(Equipment::class)->willReturn($equipmentRepository);

        $anovaData = $this->createStub(AnovaData::class);

        $result = (new EquipmentTypeQueryGroup(5))->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            101 => 'Trail Shoes',
            102 => 'Road Shoes',
        ], $result);
    }

    public function testLoadAllGroupsReturnsEmptyArrayWhenNoEquipmentOfThatTypeExists(): void
    {
        $account = $this->createStub(Account::class);

        $equipmentRepository = $this->createMock(EquipmentRepository::class);
        $equipmentRepository->method('findForTypeId')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturn($equipmentRepository);

        $anovaData = $this->createStub(AnovaData::class);

        $result = (new EquipmentTypeQueryGroup(5))->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([], $result);
    }
}
