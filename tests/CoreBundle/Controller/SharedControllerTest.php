<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Cache;
use DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Model\Factory;
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

    #[DataProvider('publicActivityUrls')]
    public function testPublicActivityUsesItsOwnerAndCanonicalUrl(string $basePath, string $legacySuffix): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: true);
        $viewer = $this->getDefaultAccount();
        $hash = $this->encodeActivityHash($activity->getId());
        $db = DB::getInstance();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO `' . PREFIX . 'sport` (`id`, `accountid`, `name`) VALUES (?, ?, ?)')
                ->execute([$activity->getSport()->getId(), $this->Account->getId(), 'Owner running']);
            $db->prepare('INSERT INTO `' . PREFIX . 'training`
                (`id`, `accountid`, `sportid`, `time`, `s`, `distance`, `kcal`, `is_public`) VALUES (?, ?, ?, ?, 1800, 5, 200, 1)')
                ->execute([$activity->getId(), $this->Account->getId(), $activity->getSport()->getId(), $activity->getTime()]);
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $activity->getId());

            $client = $this->makeAuthenticatedClient();
            $activityUrl = 'https://example.test' . $basePath . '/shared/' . $hash;
            $crawler = $client->request('GET', $activityUrl . $legacySuffix . '?user=' . $viewer->getUsername(), server: [
                'SCRIPT_NAME' => $basePath . '/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
            ]);

            $this->assertStatusCode(200, $client);
            $this->assertStringContainsString($this->Account->getUsername(), $crawler->filter('title')->text());
            $this->assertStringContainsString('Owner running', (string) $crawler->filter('meta[property="og:title"]')->attr('content'));
            $this->assertSame($activityUrl, $crawler->filter('meta[property="og:url"]')->attr('content'));
            $athleteUrl = $basePath . '/athlete/' . $this->Account->getUsername();
            $this->assertCount(1, $crawler->filter('.panel-menu a[href="' . $activityUrl . '"][target="_blank"] .fa-link'));
            $this->assertCount(1, $crawler->filter('.panel-menu a[href="' . $athleteUrl . '"][target="_blank"] .fa-table'));
        } finally {
            $db->rollBack();
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $activity->getId());
        }
    }

    public static function publicActivityUrls(): iterable
    {
        yield 'canonical URL at the web root' => ['', ''];
        yield 'legacy URL with a deployment prefix' => ['/runalyze', '&legacy'];
    }

    public function testPublicActivityEmbedLinksToTheOwnersPublicProfile(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: true)
            ->setDistance(5.0)
            ->setElevation(30)
            ->setKcal(250)
            ->setPulseAvg(145)
            ->setPulseMax(162)
            ->setTrimp(40);
        $this->getTrainingRepository()->save($activity);
        $em = $this->getEntityManager();
        $em->persist(new Conf()->setAccount($this->Account)->setCategory('privacy')->setKey('TRAINING_LIST_PUBLIC')->setValue('true'));
        $em->flush();

        $crawler = $this->Client->request('GET', '/shared/' . $this->encodeActivityHash($activity->getId()) . '?mode=iframe');

        $this->assertStatusCode(200, $this->Client);
        $this->assertCount(1, $crawler->filter('#shared-iframe #training-overview'));
        $this->assertSame(
            'http://localhost/athlete/' . $this->Account->getUsername(),
            $crawler->filter('#shared-iframe .panel-heading a')->attr('href')
        );
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

    public function testPublicWeeklyPlotKeepsItsOwnerAndFiltersInNavigation(): void
    {
        $em = $this->getEntityManager();
        $em->persist(new Conf()->setAccount($this->Account)->setCategory('privacy')->setKey('TRAINING_LIST_PUBLIC')->setValue('true'));
        $em->flush();

        $url = '/runalyze/athlete/' . $this->Account->getUsername();
        $sport = $this->getRunningSportForAccount($this->Account)->getId();
        $query = '?type=week&y=2025&sportid=' . $sport . '&group=types&analysis=trimp';
        $crawler = $this->Client->request('GET', $url . $query, server: [
            'SCRIPT_NAME' => '/runalyze/index.php',
            'SCRIPT_FILENAME' => '/var/www/public/index.php',
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Weekly chart:', $crawler->filter('.panel-heading h1')->text());
        $this->assertGreaterThan(0, $crawler->filter('.panel-menu li.active a[href="' . $url . $query . '"]')->count());
    }
}
