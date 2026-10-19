<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Activity;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class ActivityContextFactoryTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('activity_context_test_' . uniqid('', true))
            ->setMail('activity_context_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordHasher);
        $account->setPassword($passwordHasher->hashPassword($account, 'irrelevant-for-this-test'));
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

    private function createTrainingForAccount(Account $account): Training
    {
        $activity = new Training()
            ->setAccount($account)
            ->setSport($this->getRunningSportForAccount($account))
            ->setTime(time())
            ->setS(1800.0);

        $this->getTrainingRepository()
            ->save($activity);

        return $activity;
    }

    private function createFactory(): ActivityContextFactory
    {
        return new ActivityContextFactory($this->getEntityManager());
    }

    public function testGetContextWiresTheActivityIntoTheActivityContext(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $context = $this->createFactory()
            ->getContext($activity);

        $this->assertSame($activity, $context->getActivity());
    }

    public function testGetContextLeavesAllOptionalRelatedEntitiesNullForAFreshActivity(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $context = $this->createFactory()
            ->getContext($activity);

        $this->assertNull($context->getTrackdata());
        $this->assertNull($context->getSwimdata());
        $this->assertNull($context->getRoute());
        $this->assertNull($context->getRaceResult());
        $this->assertFalse($context->hasTrackdata());
        $this->assertFalse($context->hasRoute());
        $this->assertFalse($context->hasSwimdata());
        $this->assertFalse($context->hasRaceResult());
    }

    public function testGetContextByIdReturnsTheContextForAnActivityOwnedByTheGivenAccount(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $context = $this->createFactory()
            ->getContextById($activity->getId(), $this->Account->getId());

        $this->assertSame($activity->getId(), $context->getActivity()->getId());
    }

    public function testGetContextByIdThrowsForAnUnknownActivityId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createFactory()
            ->getContextById(999999999, $this->Account->getId());
    }

    public function testGetContextByIdThrowsWhenTheActivityBelongsToADifferentAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount);

        $this->expectException(InvalidArgumentException::class);

        $this->createFactory()
            ->getContextById($activity->getId(), $this->Account->getId());
    }
}
