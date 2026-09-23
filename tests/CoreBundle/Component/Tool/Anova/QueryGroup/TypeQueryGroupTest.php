<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Type as TypeQueryGroup;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Entity\TypeRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class TypeQueryGroupTest extends TestCase
{
    public function testAddSelectionToQueryJoinsTypeAndSelectsItsId(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('ty.id as t')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('join')
            ->with('tr.type', 'ty')
            ->willReturn($queryBuilder);

        (new TypeQueryGroup())->addSelectionToQuery($queryBuilder, 'tr', 't', 'sp');
    }

    public function testShowEmptyGroupsReturnsTrue(): void
    {
        $this->assertTrue((new TypeQueryGroup())->showEmptyGroups());
    }

    public function testLoadAllGroupsReturnsOnlyTypesBelongingToSelectedSports(): void
    {
        $runningSport = $this->createMock(Sport::class);
        $runningSport->method('getId')
            ->willReturn(1);

        $cyclingSport = $this->createMock(Sport::class);
        $cyclingSport->method('getId')
            ->willReturn(2);

        $easyRun = $this->createMock(Type::class);
        $easyRun->method('getId')
            ->willReturn(10);
        $easyRun->method('getName')
            ->willReturn('Easy Run');
        $easyRun->method('getSport')
            ->willReturn($runningSport);

        $intervalRun = $this->createMock(Type::class);
        $intervalRun->method('getId')
            ->willReturn(11);
        $intervalRun->method('getName')
            ->willReturn('Intervals');
        $intervalRun->method('getSport')
            ->willReturn($runningSport);

        $roadRide = $this->createMock(Type::class);
        $roadRide->method('getId')
            ->willReturn(20);
        $roadRide->method('getName')
            ->willReturn('Road Ride');
        $roadRide->method('getSport')
            ->willReturn($cyclingSport);

        $selectedRunningSport = $this->createMock(Sport::class);
        $selectedRunningSport->method('getId')
            ->willReturn(1);

        $account = $this->createMock(Account::class);

        $typeRepository = $this->createMock(TypeRepository::class);
        $typeRepository->method('findAllFor')
            ->with($account)
            ->willReturn([$easyRun, $intervalRun, $roadRide]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->with(Type::class)->willReturn($typeRepository);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([$selectedRunningSport]);

        $result = (new TypeQueryGroup())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            10 => 'Easy Run',
            11 => 'Intervals',
        ], $result);
    }

    public function testLoadAllGroupsReturnsEmptyArrayWhenNoTypeMatchesSelectedSports(): void
    {
        $cyclingSport = $this->createMock(Sport::class);
        $cyclingSport->method('getId')
            ->willReturn(2);

        $roadRide = $this->createMock(Type::class);
        $roadRide->method('getSport')
            ->willReturn($cyclingSport);

        $selectedRunningSport = $this->createMock(Sport::class);
        $selectedRunningSport->method('getId')
            ->willReturn(1);

        $account = $this->createMock(Account::class);

        $typeRepository = $this->createMock(TypeRepository::class);
        $typeRepository->method('findAllFor')
            ->willReturn([$roadRide]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturn($typeRepository);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([$selectedRunningSport]);

        $result = (new TypeQueryGroup())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([], $result);
    }
}
