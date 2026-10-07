<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal\Data;

use DateTime;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class RaceResultsControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestRaceResults(Account $account): array
    {
        $this->loginClient($this->Client, $account, 'default');
        $this->Client->request('GET', '/_internal/data/race-results/all');

        $this->isSuccessful($this->Client->getResponse());

        return json_decode($this->Client->getResponse()->getContent(), true);
    }

    private function persistRaceResult(
        int $timestamp,
        float $officialDistance,
        float $officialTimeSeconds,
        ?float $vo2max = null,
        ?float $vo2maxByTime = null,
        ?float $vo2maxWithElevation = null,
        string $name = 'Test Race'
    ): Raceresult {
        $training = $this->getActivityForDefaultAccount($timestamp, $officialTimeSeconds, $officialDistance);

        $this->getEntityManager()
            ->persist($training);
        $this->getEntityManager()
            ->flush();

        $training
            ->setVO2max($vo2max)
            ->setVO2maxByTime($vo2maxByTime)
            ->setVO2maxWithElevation($vo2maxWithElevation);

        $this->getEntityManager()
            ->flush();

        $raceResult = new Raceresult()
            ->setAccount($this->Account)
            ->setActivity($training)
            ->setName($name)
            ->setOfficialDistance($officialDistance)
            ->setOfficialTime($officialTimeSeconds)
            ->setOfficiallyMeasured(true);

        $this->getEntityManager()
            ->persist($raceResult);
        $this->getEntityManager()
            ->flush();

        return $raceResult;
    }

    /**
     * @param int[] $activityIds
     */
    private function markActivitiesAsFunRaces(array $activityIds): void
    {
        $plugin = new Plugin()
            ->setKey('RunalyzePluginStat_Wettkampf')
            ->setType(Plugin::TYPE_STAT)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(0)
            ->setAccount($this->Account);

        $this->getEntityManager()
            ->persist($plugin);

        $pluginConf = new PluginConf()
            ->setConfig('fun_ids')
            ->setValue(implode(',', $activityIds))
            ->setPlugin($plugin);

        $this->getEntityManager()
            ->persist($pluginConf);
        $this->getEntityManager()
            ->flush();
    }

    public function testAllRaceResultsActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/_internal/data/race-results/all');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testAllRaceResultsActionReturnsEmptyArrayForAccountWithoutRaceResults(): void
    {
        $data = $this->requestRaceResults($this->getEmptyAccount());

        $this->assertSame([], $data);
    }

    public function testAllRaceResultsActionReturnsCorrectlyMappedFieldsForASingleRaceResult(): void
    {
        $timestamp = 1700000000;
        $sport = $this->getDefaultAccountsRunningSport();

        $this->persistRaceResult($timestamp, 10.0, 2400.0, name: 'Sunday 10k');

        $data = $this->requestRaceResults($this->Account);

        $this->assertCount(1, $data);
        $race = $data[0];

        $this->assertSame('Sunday 10k', $race['name']);
        $this->assertSame(new DateTime('@' . $timestamp)->format('c'), $race['date']);
        $this->assertSame($sport->getId(), $race['sport_id']);
        $this->assertEqualsWithDelta(10.0, $race['distance'], 0.001);
        $this->assertEqualsWithDelta(2400.0, $race['duration'], 0.001);
        $this->assertTrue($race['officially_measured']);
        $this->assertNull($race['place_total']);
        $this->assertNull($race['place_gender']);
        $this->assertNull($race['place_ageclass']);
        $this->assertNull($race['participants_total']);
        $this->assertNull($race['participants_gender']);
        $this->assertNull($race['participants_ageclass']);
        $this->assertNull($race['vo2max']);
        $this->assertNull($race['vo2max_by_time']);
        $this->assertNull($race['vo2max_with_elevation']);
        $this->assertFalse($race['is_fun']);
        $this->assertIsFloat($race['age_grade']);
    }

    public function testAllRaceResultsActionReturnsVO2maxFieldsFromTheAssociatedActivity(): void
    {
        $this->persistRaceResult(1700000000, 10.0, 2400.0, vo2max: 50.5, vo2maxByTime: 51.2, vo2maxWithElevation: 52.0);

        $data = $this->requestRaceResults($this->Account);

        $this->assertEqualsWithDelta(50.5, $data[0]['vo2max'], 0.01);
        $this->assertEqualsWithDelta(51.2, $data[0]['vo2max_by_time'], 0.01);
        $this->assertEqualsWithDelta(52.0, $data[0]['vo2max_with_elevation'], 0.01);
    }

    public function testAllRaceResultsActionMarksOnlyActivitiesListedAsFunRaces(): void
    {
        $funRace = $this->persistRaceResult(1700000000, 10.0, 2400.0, name: 'Fun run');
        $this->persistRaceResult(1700100000, 21.1, 5400.0, name: 'Half marathon');

        $this->markActivitiesAsFunRaces([$funRace->getActivity()->getId()]);

        $data = $this->requestRaceResults($this->Account);
        $byName = [];
        foreach ($data as $race) {
            $byName[$race['name']] = $race;
        }

        $this->assertTrue($byName['Fun run']['is_fun']);
        $this->assertFalse($byName['Half marathon']['is_fun']);
    }

    public function testAllRaceResultsActionReturnsAllRaceResultsForTheAccount(): void
    {
        $this->persistRaceResult(1700000000, 10.0, 2400.0, name: 'Race A');
        $this->persistRaceResult(1700100000, 21.1, 5400.0, name: 'Race B');
        $this->persistRaceResult(1700200000, 42.2, 12000.0, name: 'Race C');

        $data = $this->requestRaceResults($this->Account);

        $this->assertCount(3, $data);
        $this->assertSame(
            ['Race A', 'Race B', 'Race C'],
            array_column($data, 'name'),
            'Order is not explicitly guaranteed by the controller/repository, but no ORDER BY is applied either; this asserts the currently observed, insertion-based order.'
        );
    }

    public function testAllRaceResultsActionDoesNotReturnRaceResultsOfAnotherAccount(): void
    {
        $this->persistRaceResult(1700000000, 10.0, 2400.0, name: 'Own race');

        $data = $this->requestRaceResults($this->getEmptyAccount());

        $this->assertSame([], $data);
    }
}
