<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class PanelControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    private function persistTrainingForDefaultAccount(int $timestamp, float $duration, float $distance): void
    {
        $training = $this->getActivityForDefaultAccount($timestamp, $duration, $distance);

        $this->getEntityManager()
            ->persist($training);
        $this->getEntityManager()
            ->flush();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function requestPanelStatisticsFor(Account $account): array
    {
        $client = $this->makeClient();
        $this->loginClient($client, $account, 'default');
        $client->request('GET', '/_internal/panel/sport');

        $this->isSuccessful($client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testThatSportStatActionReturnsAllFourEmptyStatisticGroupsForAccountWithoutTrainings(): void
    {
        $statistics = $this->requestPanelStatisticsFor($this->getEmptyAccount());

        $this->assertSame([], $statistics['weekStatistics']);
        $this->assertSame([], $statistics['monthStatistics']);
        $this->assertSame([], $statistics['yearStatistics']);
        $this->assertSame([], $statistics['totalStatistics']);
    }

    public function testThatSportStatActionIncludesARecentTrainingInWeekMonthYearAndTotalStatistics(): void
    {
        $this->persistTrainingForDefaultAccount(time(), 1800, 5.0);

        $statistics = $this->requestPanelStatisticsFor($this->Account);

        foreach (['weekStatistics', 'monthStatistics', 'yearStatistics', 'totalStatistics'] as $key) {
            $this->assertCount(1, $statistics[$key], $key . ' must contain exactly one sport group.');
            $this->assertSame(1, (int) $statistics[$key][0]['num'], $key . ' must count exactly one training.');
            $this->assertSame(5.0, (float) $statistics[$key][0]['distance'], $key . ' must sum up to the training distance.');
        }
    }

    public function testThatSportStatActionExcludesAnOldTrainingFromWeekMonthAndYearStatisticsButKeepsItInTotalStatistics(): void
    {
        $this->persistTrainingForDefaultAccount(strtotime('-2 years'), 3600, 10.0);
        $this->persistTrainingForDefaultAccount(time(), 1800, 5.0);

        $statistics = $this->requestPanelStatisticsFor($this->Account);

        foreach (['weekStatistics', 'monthStatistics', 'yearStatistics'] as $key) {
            $this->assertSame(1, (int) $statistics[$key][0]['num'], $key . ' must only include the recent training.');
            $this->assertSame(5.0, (float) $statistics[$key][0]['distance'], $key . ' must only include the recent training.');
        }

        $this->assertSame(2, (int) $statistics['totalStatistics'][0]['num']);
        $this->assertSame(15.0, (float) $statistics['totalStatistics'][0]['distance']);
    }

    public function testThatSportStatActionKeepsStatisticsOfDifferentAccountsSeparate(): void
    {
        $this->persistTrainingForDefaultAccount(time(), 1800, 5.0);

        $statistics = $this->requestPanelStatisticsFor($this->getEmptyAccount());

        $this->assertSame([], $statistics['totalStatistics']);
    }
}
