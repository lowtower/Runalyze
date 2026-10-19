<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrackdataRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class ViewControllerTrackdataTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('view_trackdata_test_' . uniqid('', true))
            ->setMail('view_trackdata_test_' . uniqid('', true) . '@example.test');

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

    private function getTrackdataRepository(): TrackdataRepository
    {
        return $this->getEntityManager()
            ->getRepository(Trackdata::class);
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

    private function createTrackdataForActivity(Training $activity): Trackdata
    {
        $trackdata = new Trackdata()
            ->setActivity($activity)
            ->setAccount($activity->getAccount())
            ->setTime([100, 200, 300, 400, 500, 600])
            ->setDistance([0.3, 0.6, 0.9, 1.25, 1.6, 2.0])
            ->setHeartrate([120, 130, 140, 140, 150, 160])
            ->setCadence([90, 90, 90, 90, 90, 90]);

        $this->getTrackdataRepository()
            ->save($trackdata);

        return $trackdata;
    }

    public function testTimeSeriesInfoActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/time-series-info');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testTimeSeriesInfoActionRendersNotPossibleWithoutTrackdata(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/time-series-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testTimeSeriesInfoActionRendersStatisticsForAnActivityWithTrackdata(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->createTrackdataForActivity($activity);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/time-series-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testSubSegmentInfoActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/sub-segments-info');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSubSegmentInfoActionRendersNotPossibleWithoutTrackdata(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/sub-segments-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testSubSegmentInfoActionRendersStatisticsForAnActivityWithTrackdata(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->createTrackdataForActivity($activity);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/' . $activity->getId() . '/sub-segments-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }
}
