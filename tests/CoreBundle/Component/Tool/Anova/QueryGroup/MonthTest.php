<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Month;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class MonthTest extends TestCase
{
    protected function setUp(): void
    {
        if (! function_exists('__')) {
            $this->markTestSkipped('Global __() translation function is not available in this test environment.');
        }
    }

    public function testAddSelectionToQueryAddsMonthExtraction(): void
    {
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('MONTH(FROM_UNIXTIME(tr.time)) as m')
            ->willReturn($queryBuilder);

        new Month()->addSelectionToQuery($queryBuilder, 'tr', 'm', 'sp');
    }

    public function testShowEmptyGroupsReturnsTrue(): void
    {
        $this->assertTrue(new Month()->showEmptyGroups());
    }

    public function testLoadAllGroupsForSameYearRangeReturnsOnlyMonthsInRange(): void
    {
        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getDateFrom')
            ->willReturn(new \DateTime('2024-03-15'));
        $anovaData->method('getDateTo')
            ->willReturn(new \DateTime('2024-07-10'));

        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);

        $result = new Month()->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([3, 4, 5, 6, 7], array_keys($result));
    }

    public function testLoadAllGroupsForRangeCrossingOneYearBoundaryWrapsAround(): void
    {
        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getDateFrom')
            ->willReturn(new \DateTime('2024-10-05'));
        $anovaData->method('getDateTo')
            ->willReturn(new \DateTime('2025-03-20'));

        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);

        $result = new Month()->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame([10, 11, 12, 1, 2, 3], array_keys($result));
    }

    public function testLoadAllGroupsForMultiYearRangeReturnsAllMonths(): void
    {
        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getDateFrom')
            ->willReturn(new \DateTime('2023-01-01'));
        $anovaData->method('getDateTo')
            ->willReturn(new \DateTime('2025-01-01'));

        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);

        $result = new Month()->loadAllGroups($entityManager, $account, $anovaData);

        $this->assertSame(range(1, 12), array_keys($result));
    }
}
