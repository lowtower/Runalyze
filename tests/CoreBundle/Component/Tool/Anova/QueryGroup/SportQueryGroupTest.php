<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Sport as SportQueryGroup;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class SportQueryGroupTest extends TestCase
{
    public function testAddSelectionToQueryUsesTheSportAliasNotTheMainAlias(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('sp.id as s')
            ->willReturn($queryBuilder);

        (new SportQueryGroup())->addSelectionToQuery($queryBuilder, 'tr', 's', 'sp');
    }

    public function testShowEmptyGroupsReturnsTrue(): void
    {
        $this->assertTrue((new SportQueryGroup())->showEmptyGroups());
    }

    public function testLoadAllGroupsReturnsOnlyTheIntersectionOfAccountSportsAndSelectedSports(): void
    {
        $running = $this->createMock(Sport::class);
        $running->method('getId')
            ->willReturn(1);
        $running->method('getName')
            ->willReturn('Running');

        $cycling = $this->createMock(Sport::class);
        $cycling->method('getId')
            ->willReturn(2);
        $cycling->method('getName')
            ->willReturn('Cycling');

        $swimming = $this->createMock(Sport::class);
        $swimming->method('getId')
            ->willReturn(3);
        $swimming->method('getName')
            ->willReturn('Swimming');

        $selectedRunning = $this->createMock(Sport::class);
        $selectedRunning->method('getId')
            ->willReturn(1);

        $selectedSwimming = $this->createMock(Sport::class);
        $selectedSwimming->method('getId')
            ->willReturn(3);

        $account = $this->createMock(Account::class);

        $sportRepository = $this->createMock(SportRepository::class);
        $sportRepository->method('findAllFor')
            ->with($account)
            ->willReturn([$running, $cycling, $swimming]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->with(Sport::class)->willReturn($sportRepository);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([$selectedRunning, $selectedSwimming]);

        $result = (new SportQueryGroup())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            1 => 'Running',
            3 => 'Swimming',
        ], $result);
    }

    public function testLoadAllGroupsReturnsEmptyArrayWhenNoSportsAreSelected(): void
    {
        $sport = $this->createMock(Sport::class);
        $sport->method('getId')
            ->willReturn(1);

        $account = $this->createMock(Account::class);

        $sportRepository = $this->createMock(SportRepository::class);
        $sportRepository->method('findAllFor')
            ->willReturn([$sport]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturn($sportRepository);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getSport')
            ->willReturn([]);

        $result = (new SportQueryGroup())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([], $result);
    }
}
