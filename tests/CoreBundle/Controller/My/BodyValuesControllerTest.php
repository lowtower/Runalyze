<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class BodyValuesControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected UserRepository $UserRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
        $this->UserRepository = $this->EntityManager->getRepository(User::class);
    }

    protected function insertBodyValuesFor(Account $account): int
    {
        $user = new User()
            ->setAccount($account)
            ->setCurrentTimestamp()
            ->setWeight(80.0);

        $this->UserRepository->save($user, $account);

        return $user->getId();
    }

    protected function reloadUser(int $id): ?User
    {
        $this->EntityManager->clear();

        return $this->UserRepository->find($id);
    }

    public function testAddAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/add');

        $this->assertStatusCode(200, $client);
    }

    public function testAddActionSavesValidSubmissionAndLeavesWeightEmptyWhenNotProvided(): void
    {
        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/body-values/add');
        $token = $crawler->filter('input[name="body_values[_token]"]')
            ->attr('value');

        $existingIds = array_map(
            static fn (User $user): ?int => $user->getId(),
            $this->UserRepository->findAllFor($this->Account)
        );

        $client->request('POST', '/my/body-values/add', [
            'body_values' => [
                '_token' => $token,
                'time' => new \DateTime()->format('d.m.Y'),
                'weight' => '',
                'fat' => '20',
                'water' => '55',
                'muscles' => '40',
                'pulse_rest' => '50',
                'pulse_max' => '180',
                'sleep_duration' => '480',
                'notes' => 'created by testAddActionSavesValidSubmissionAndLeavesWeightEmptyWhenNotProvided',
            ],
        ]);

        $this->assertStatusCode(302, $client);

        $this->EntityManager->clear();

        $newUsers = array_values(array_filter(
            $this->UserRepository->findAllFor($this->Account),
            static fn (User $user): bool => ! in_array($user->getId(), $existingIds, true)
        ));

        $this->assertCount(1, $newUsers);
        $this->assertNull($newUsers[0]->getWeight());
        $this->assertSame(20.0, $newUsers[0]->getFat());
        $this->assertSame(55.0, $newUsers[0]->getWater());
        $this->assertSame(40.0, $newUsers[0]->getMuscles());
        $this->assertSame(50, $newUsers[0]->getPulseRest());
        $this->assertSame(180, $newUsers[0]->getPulseMax());
        $this->assertSame(480, $newUsers[0]->getSleepDuration());
        $this->assertSame(
            'created by testAddActionSavesValidSubmissionAndLeavesWeightEmptyWhenNotProvided',
            $newUsers[0]->getNotes()
        );
    }

    public function testEditActionThrowsNotFoundForUnknownId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/314159/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testEditActionThrowsNotFoundForWrongUser(): void
    {
        $id = $this->insertBodyValuesFor($this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/' . $id . '/edit');

        $this->assertStatusCode(404, $client);
    }

    public function testEditActionRendersFormForCorrectUser(): void
    {
        $id = $this->insertBodyValuesFor($this->Account);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/' . $id . '/edit');

        $this->assertStatusCode(200, $client);
    }

    public function testEditActionSavesValidSubmissionAndRedirectsToTable(): void
    {
        $id = $this->insertBodyValuesFor($this->Account);

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/body-values/' . $id . '/edit');
        $token = $crawler->filter('input[name="body_values[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/body-values/' . $id . '/edit', [
            'body_values' => [
                '_token' => $token,
                'time' => new \DateTime()->format('d.m.Y'),
                'weight' => '82.5',
                'fat' => '18',
                'water' => '58',
                'muscles' => '42',
                'pulse_rest' => '48',
                'pulse_max' => '175',
                'sleep_duration' => '420',
                'notes' => 'updated by testEditActionSavesValidSubmissionAndRedirectsToTable',
            ],
        ]);

        $this->assertStatusCode(302, $client);

        $updated = $this->reloadUser($id);

        $this->assertNotNull($updated->getWeight());
        $this->assertSame(18.0, $updated->getFat());
        $this->assertSame(58.0, $updated->getWater());
        $this->assertSame(42.0, $updated->getMuscles());
        $this->assertSame(48, $updated->getPulseRest());
        $this->assertSame(175, $updated->getPulseMax());
        $this->assertSame(420, $updated->getSleepDuration());
        $this->assertSame(
            'updated by testEditActionSavesValidSubmissionAndRedirectsToTable',
            $updated->getNotes()
        );
    }

    public function testDeleteActionThrowsNotFoundForUnknownId(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/314159/delete');

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteActionThrowsNotFoundForWrongUser(): void
    {
        $id = $this->insertBodyValuesFor($this->getEmptyAccount());

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/' . $id . '/delete');

        $this->assertStatusCode(404, $client);
    }

    public function testDeleteActionRemovesEntryAndRedirectsForCorrectUser(): void
    {
        $id = $this->insertBodyValuesFor($this->Account);

        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/' . $id . '/delete');

        $this->assertStatusCode(302, $client);
        $this->assertNull($this->reloadUser($id));
    }

    public function testTableAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/table');

        $this->assertStatusCode(200, $client);
    }
}
