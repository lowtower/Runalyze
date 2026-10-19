<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Type as TypeQueryGroup;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Bundle\CoreBundle\Repository\TypeRepository;

final class TypeQueryGroupTest extends TestCase
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

        new TypeQueryGroup()->addSelectionToQuery($queryBuilder, 'tr', 't', 'sp');
    }

    public function testShowEmptyGroupsReturnsTrue(): void
    {
        $this->assertTrue(new TypeQueryGroup()->showEmptyGroups());
    }

    public function testLoadAllGroupsReturnsOnlyTypesBelongingToSelectedSports(): void
    {
        $runningSport = $this->createStub(Sport::class);
        $runningSport->method('getId')
            ->willReturn(1);

        $cyclingSport = $this->createStub(Sport::class);
        $cyclingSport->method('getId')
            ->willReturn(2);

        $easyRun = $this->createStub(Type::class);
        $easyRun->method('getId')
            ->willReturn(10);
        $easyRun->method('getName')
            ->willReturn('Easy Run');
        $easyRun->method('getSport')
            ->willReturn($runningSport);

        $intervalRun = $this->createStub(Type::class);
        $intervalRun->method('getId')
            ->willReturn(11);
        $intervalRun->method('getName')
            ->willReturn('Intervals');
        $intervalRun->method('getSport')
            ->willReturn($runningSport);

        $roadRide = $this->createStub(Type::class);
        $roadRide->method('getId')
            ->willReturn(20);
        $roadRide->method('getName')
            ->willReturn('Road Ride');
        $roadRide->method('getSport')
            ->willReturn($cyclingSport);

        $selectedRunningSport = $this->createStub(Sport::class);
        $selectedRunningSport->method('getId')
            ->willReturn(1);

        $account = $this->createStub(Account::class);

        $typeRepository = $this->createStub(TypeRepository::class);
        $typeRepository->method('findAllFor')
            ->willReturnCallback(function (Account $requestedAccount) use ($account, $easyRun, $intervalRun, $roadRide): array {
                $this->assertSame($account, $requestedAccount);

                return [$easyRun, $intervalRun, $roadRide];
            });

        $entityManager = $this->createStub(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function (string $className) use ($typeRepository): TypeRepository {
                $this->assertSame(Type::class, $className);

                return $typeRepository;
            });

        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([$selectedRunningSport]);

        $result = new TypeQueryGroup()->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            10 => 'Easy Run',
            11 => 'Intervals',
        ], $result);
    }

    public function testLoadAllGroupsReturnsEmptyArrayWhenNoTypeMatchesSelectedSports(): void
    {
        $cyclingSport = $this->createStub(Sport::class);
        $cyclingSport->method('getId')
            ->willReturn(2);

        $roadRide = $this->createStub(Type::class);
        $roadRide->method('getSport')
            ->willReturn($cyclingSport);

        $selectedRunningSport = $this->createStub(Sport::class);
        $selectedRunningSport->method('getId')
            ->willReturn(1);

        $account = $this->createStub(Account::class);

        $typeRepository = $this->createStub(TypeRepository::class);
        $typeRepository->method('findAllFor')
            ->willReturn([$roadRide]);

        $entityManager = $this->createStub(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturn($typeRepository);

        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([$selectedRunningSport]);

        $result = new TypeQueryGroup()->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([], $result);
    }
}
