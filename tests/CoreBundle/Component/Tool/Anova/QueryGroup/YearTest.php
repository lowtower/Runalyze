<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Year;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class YearTest extends TestCase
{
    public function testAddSelectionToQueryAddsYearExtraction(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('YEAR(FROM_UNIXTIME(tr.time)) as y')
            ->willReturn($queryBuilder);

        (new Year())->addSelectionToQuery($queryBuilder, 'tr', 'y', 'sp');
    }

    public function testShowEmptyGroupsReturnsTrue(): void
    {
        $this->assertTrue((new Year())->showEmptyGroups());
    }

    public function testLoadAllGroupsReturnsInclusiveYearRangeAsStrings(): void
    {
        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFrom')
            ->willReturn(new \DateTime('2022-06-01'));
        $anovaData->method('getDateTo')
            ->willReturn(new \DateTime('2024-01-01'));

        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);

        $result = (new Year())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            2022 => '2022',
            2023 => '2023',
            2024 => '2024',
        ], $result);
    }

    public function testLoadAllGroupsForSingleYearRangeReturnsOnlyThatYear(): void
    {
        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFrom')
            ->willReturn(new \DateTime('2024-01-01'));
        $anovaData->method('getDateTo')
            ->willReturn(new \DateTime('2024-12-31'));

        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);

        $result = (new Year())->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([
            2024 => '2024',
        ], $result);
    }
}
