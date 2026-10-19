<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class SharedControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();

        ob_start();
    }

    protected function tearDown(): void
    {
        gc_collect_cycles();

        ob_end_clean();

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('shared_test_' . uniqid('', true))
            ->setMail('shared_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function getRunningSportForAccount(Account $account): Sport
    {
        return $this->getEntityManager()
            ->getRepository(Sport::class)
            ->findOneBy([
                'account' => $account->getId(),
                'name' => 'Running',
            ]);
    }

    private function getTrainingRepository(): TrainingRepository
    {
        return $this->getEntityManager()
            ->getRepository(Training::class);
    }

    private function createTrainingForAccount(Account $account, bool $isPublic): Training
    {
        $activity = new Training()
            ->setAccount($account)
            ->setSport($this->getRunningSportForAccount($account))
            ->setTime(time())
            ->setS(1800.0)
            ->setPublic($isPublic);

        $this->getTrainingRepository()
            ->save($activity);

        return $activity;
    }

    private function encodeActivityHash(int $activityId): string
    {
        return base_convert((string) $activityId, 10, 35);
    }

    private function request(string $method, string $uri): void
    {
        $this->Client->request($method, $uri);
    }

    public function testSharedTrainingActionRendersInvalidPageForAHashDecodingToActivityIdZero(): void
    {
        $this->request('GET', '/shared/0');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'no activity found',
            strtolower((string) $this->Client->getResponse()->getContent())
        );
    }

    public function testSharedTrainingActionRendersInvalidPageForAnUnknownActivityId(): void
    {
        $this->request('GET', '/shared/' . $this->encodeActivityHash(999999999));

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'no activity found',
            strtolower((string) $this->Client->getResponse()->getContent())
        );
    }

    public function testSharedTrainingActionRendersInvalidPageForAPrivateActivity(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: false);

        $this->request('GET', '/shared/' . $this->encodeActivityHash($activity->getId()));

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'no activity found',
            strtolower((string) $this->Client->getResponse()->getContent())
        );
    }

    public function testSharedTrainingActionIsReachableWithoutBeingLoggedIn(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: false);

        $this->request('GET', '/shared/' . $this->encodeActivityHash($activity->getId()));

        $this->isSuccessful($this->Client->getResponse(), true);
    }

    public function testOldSharedUserActionRedirectsPermanentlyToSharedAthlete(): void
    {
        $this->request('GET', '/shared/' . $this->Account->getUsername() . '/');

        $this->assertStatusCode(301, $this->Client);
        $this->assertSame('/athlete/' . $this->Account->getUsername(), $this->Client->getResponse()->headers->get('Location'));
    }

    public function testSharedUserActionRendersInvalidPageForAnUnknownUsername(): void
    {
        $this->request('GET', '/athlete/this-username-does-not-exist-' . uniqid('', true));

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'no athlete found',
            strtolower((string) $this->Client->getResponse()->getContent())
        );
    }

    public function testPublicUserFeedActionReturnsXmlForAnUnknownUsername(): void
    {
        $this->request('GET', '/athlete/this-username-does-not-exist-' . uniqid('', true) . '/feed');

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('text/xml; charset=UTF-8', $this->Client->getResponse()->headers->get('content-type'));
    }

    public function testPublicUserFeedActionIsReachableWithoutBeingLoggedIn(): void
    {
        $this->request('GET', '/athlete/' . $this->Account->getUsername() . '/feed');

        $this->isSuccessful($this->Client->getResponse(), true);
        $this->assertSame('text/xml; charset=UTF-8', $this->Client->getResponse()->headers->get('content-type'));
    }
}
