<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class RaceResultControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected RaceresultRepository $RaceresultRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
        $this->RaceresultRepository = $this->EntityManager->getRepository(Raceresult::class);
    }

    protected function insertActivityFor(Account $account): Training
    {
        $sport = new Sport()
            ->setName('Test Sport')
            ->setAccount($account);

        $activity = new Training()
            ->setSport($sport)
            ->setTime(time())
            ->setS(3600.0)
            ->setAccount($account);

        $this->EntityManager->persist($sport);
        $this->EntityManager->persist($activity);
        $this->EntityManager->flush();

        return $activity;
    }

    protected function insertRaceResultFor(Training $activity, Account $account): Raceresult
    {
        $raceResult = new Raceresult()
            ->setAccount($account)
            ->setActivity($activity)
            ->setOfficialTime(3600.0);

        $this->RaceresultRepository->save($raceResult);

        return $raceResult;
    }

    /**
     * The HTTP client boots its own kernel with its own EntityManager for
     * every request. Changes made through that request are correctly
     * persisted to the database, but this test's own EntityManager keeps
     * serving the entity from its identity map instead of reading the
     * updated row. Clearing it forces a fresh read from the database.
     */
    protected function reloadRaceresult(int $activityId, Account $account): ?Raceresult
    {
        $this->EntityManager->clear();

        return $this->RaceresultRepository->findForAccount($activityId, $account->getId());
    }

    public function testRaceresultFormActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/raceresult/1');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testRaceresultFormActionThrowsAccessDeniedForUnknownActivity(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/314159');

        $this->assertStatusCode(403, $client);
    }

    public function testRaceresultFormActionThrowsAccessDeniedForActivityOfOtherAccount(): void
    {
        $activity = $this->insertActivityFor($this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activity->getId());

        $this->assertStatusCode(403, $client);
    }

    public function testRaceresultFormActionRendersFormForNewRaceResult(): void
    {
        $activity = $this->insertActivityFor($this->Account);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activity->getId());

        $this->assertStatusCode(200, $client);
    }

    public function testRaceresultFormActionRendersFormForExistingRaceResult(): void
    {
        $activity = $this->insertActivityFor($this->Account);
        $this->insertRaceResultFor($activity, $this->Account);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activity->getId());

        $this->assertStatusCode(200, $client);
    }

    public function testRaceresultFormActionSavesValidSubmissionWithoutOfficialDistance(): void
    {
        $activity = $this->insertActivityFor($this->Account);
        $activityId = $activity->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/raceresult/' . $activityId);
        $token = $crawler->filter('input[name="race_result[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/raceresult/' . $activityId, [
            'race_result' => [
                '_token' => $token,
                'name' => 'City Marathon',
                'official_time' => '10800',
                'official_distance' => '',
            ],
        ]);

        $this->assertStatusCode(200, $client);

        $saved = $this->reloadRaceresult($activityId, $this->Account);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Raceresult::class, $saved);
        $this->assertSame('City Marathon', $saved->getName());
        $this->assertNull($saved->getOfficialDistance());
    }

    public function testRaceresultFormActionSavesValidSubmissionWithOfficialDistance(): void
    {
        $activity = $this->insertActivityFor($this->Account);
        $activityId = $activity->getId();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/raceresult/' . $activityId);
        $token = $crawler->filter('input[name="race_result[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/raceresult/' . $activityId, [
            'race_result' => [
                '_token' => $token,
                'name' => 'City Marathon',
                'official_time' => '10800',
                'official_distance' => '10',
                'officially_measured' => '1',
                'place_total' => '42',
            ],
        ]);

        $this->assertStatusCode(200, $client);

        $saved = $this->reloadRaceresult($activityId, $this->Account);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Raceresult::class, $saved);
        $this->assertNotNull($saved->getOfficialDistance());
        $this->assertTrue($saved->getOfficiallyMeasured());
        $this->assertSame(42, $saved->getPlaceTotal());
    }

    public function testRaceresultDeleteActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/raceresult/1/delete');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testRaceresultDeleteActionThrowsAccessDeniedForUnknownActivity(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/314159/delete');

        $this->assertStatusCode(403, $client);
    }

    public function testRaceresultDeleteActionThrowsAccessDeniedWhenNoRaceResultExistsForActivity(): void
    {
        $activity = $this->insertActivityFor($this->Account);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activity->getId() . '/delete');

        $this->assertStatusCode(403, $client);
    }

    public function testRaceresultDeleteActionThrowsAccessDeniedWhenRaceResultBelongsToOtherAccount(): void
    {
        $activity = $this->insertActivityFor($this->getEmptyAccount());
        $this->insertRaceResultFor($activity, $this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activity->getId() . '/delete');

        $this->assertStatusCode(403, $client);
    }

    public function testRaceresultDeleteActionRemovesRaceResultForCorrectAccount(): void
    {
        $activity = $this->insertActivityFor($this->Account);
        $this->insertRaceResultFor($activity, $this->Account);
        $activityId = $activity->getId();

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/' . $activityId . '/delete');

        $this->assertStatusCode(200, $client);
        $this->assertNull($this->reloadRaceresult($activityId, $this->Account));
    }

    public function testPerformanceChartActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/raceresult/performance-chart');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testPerformanceChartActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/performance-chart');

        $this->assertStatusCode(200, $client);
    }

    public function testPerformanceChartActionUsesAccountSpecificLookupWhenGenderAndBirthYearAreKnown(): void
    {
        $this->Account
            ->setGender(\Runalyze\Profile\Athlete\Gender::FEMALE)
            ->setBirthyear((int) date('Y') - 30);
        $this->EntityManager->persist($this->Account);
        $this->EntityManager->flush();

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/raceresult/performance-chart');

        $this->assertStatusCode(200, $client);
    }
}
