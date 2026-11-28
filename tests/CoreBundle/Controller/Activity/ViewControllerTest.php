<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Cache;
use DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Model\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
final class ViewControllerTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('view_test_' . uniqid('', true))
            ->setMail('view_test_' . uniqid('', true) . '@example.test');

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

    private function request(string $method, string $uri): void
    {
        ob_start();
        $this->Client->request($method, $uri);
        ob_end_clean();
    }

    private function createTrainingForAccount(Account $account, bool $isPublic = false): Training
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

    public function testDisplayActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->request('GET', '/activity/' . $activity->getId());

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDisplayActionReturns404ForAnUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/999999999?silent=1');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testDisplayActionAllowsViewingOwnPrivateActivity(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: false);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activity->getId() . '?silent=1');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testDisplayActionReturns404ForAPrivateActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, isPublic: false);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '?silent=1');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testDisplayActionAllowsViewingAPublicActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, isPublic: true);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '?silent=1');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    #[DataProvider('shareMenuVariants')]
    public function testDisplayActionRendersTheShareMenuWithTheRequestUrl(bool $isPublic, string $host, bool $dashboard = false): void
    {
        $activity = $this->createTrainingForAccount($this->Account, $isPublic);
        $id = $activity->getId();
        $sportId = $activity->getSport()->getId();
        $accountId = $this->Account->getId();
        $db = DB::getInstance();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO ' . PREFIX . 'sport (id, accountid, name) VALUES (?, ?, ?)')
                ->execute([$sportId, $accountId, 'Running']);
            $db->prepare('INSERT INTO ' . PREFIX . 'training (id, accountid, sportid, time, s, distance, is_public, title) VALUES (?, ?, ?, ?, 1800, 5, ?, ?)')
                ->execute([$id, $accountId, $sportId, $activity->getTime(), (int) $isPublic, 'Tempo & hills']);
            Cache::delete('training' . $id);
            unset(Factory::$StaticCache['sport']);

            $this->loginClient($this->Client, $this->Account, 'default');
            $base = 'https://' . $host . '/runalyze';
            $path = $dashboard ? '/dashboard?id=' . $id : '/activity/' . $id;
            $page = $this->Client->request('GET', $base . $path, server: [
                'SCRIPT_NAME' => '/runalyze/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
            ]);
            $this->assertStatusCode(200, $this->Client);

            $sharedUrl = $base . '/shared/' . base_convert((string) $id, 10, 35);
            $this->assertCount($isPublic ? 1 : 0, $page->filter('.panel-menu a[href="' . $sharedUrl . '"]'));
            $twitter = $page->filter('.panel-menu a[href^="https://twitter.com/intent/tweet?"]');
            parse_str((string) parse_url((string) $twitter->attr('href'), PHP_URL_QUERY), $tweet);
            $this->assertStringContainsString('Tempo & hills', (string) $tweet['text']);

            $facebook = $page->filter('.panel-menu a[href^="https://www.facebook.com/dialog/feed?"]');
            $googlePlus = $page->filter('.panel-menu a[href^="https://plus.google.com/share?"]');
            if ($isPublic && $host !== 'localhost') {
                $this->assertSame($sharedUrl . '?utm_medium=referral&utm_source=twitter', $tweet['url']);
                parse_str((string) parse_url((string) $facebook->attr('href'), PHP_URL_QUERY), $facebookParameters);
                $this->assertSame($sharedUrl . '?utm_medium=referral&utm_source=facebook', $facebookParameters['link']);
                $this->assertSame($base . '/assets/images/runalyze.png', $facebookParameters['picture']);
                $this->assertCount(1, $googlePlus);
            } else {
                $this->assertArrayNotHasKey('url', $tweet);
                $this->assertCount(0, $facebook);
                $this->assertCount(0, $googlePlus);
            }
        } finally {
            $db->rollBack();
            unset(Factory::$StaticCache['sport']);
            Cache::delete('training' . $id);
        }
    }

    public static function shareMenuVariants(): iterable
    {
        yield 'public activity' => [true, 'example.test'];
        yield 'private activity' => [false, 'example.test'];
        yield 'public activity on localhost' => [true, 'localhost'];
        yield 'public activity in the dashboard' => [true, 'example.test', true];
    }

    public function testDisplayActionChangePrivacyTogglesPrivacyForOwnActivity(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: false);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activity->getId() . '?action=changePrivacy&silent=1');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $reloadedActivity);
        $this->assertTrue($reloadedActivity->isPublic());
    }

    public function testDisplayActionChangePrivacyReturns404WhenNotOwnerEvenForAPublicActivity(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, isPublic: true);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '?action=changePrivacy&silent=1');

        $this->assertStatusCode(404, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedActivity = $this->getTrainingRepository()
            ->find($activity->getId());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $reloadedActivity);
        $this->assertTrue($reloadedActivity->isPublic(), "The other account's activity must remain unchanged.");
    }

    public function testDisplayActionDeleteRemovesOwnActivity(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $activityId = $activity->getId();
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activityId . '?action=delete');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );

        $this->getEntityManager()
            ->clear();
        $this->assertNull($this->getTrainingRepository()->find($activityId));
    }

    public function testDisplayActionDeleteReturns404WhenNotOwnerEvenForAPublicActivity(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, isPublic: true);
        $activityId = $activity->getId();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activityId . '?action=delete');

        $this->assertStatusCode(404, $this->Client);

        $this->getEntityManager()
            ->clear();
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $this->getTrainingRepository()->find($activityId), "The other account's activity must not have been deleted.");
    }

    public function testVo2maxInfoActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->request('GET', '/activity/' . $activity->getId() . '/vo2max-info');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testVo2maxInfoActionReturns404ForAnUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/999999999/vo2max-info');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testVo2maxInfoActionReturns404ForAnActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '/vo2max-info');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testSplitsInfoActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->request('GET', '/activity/' . $activity->getId() . '/splits-info');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSplitsInfoActionRendersNotPossibleWhenNoLegacyTrackdataExists(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activity->getId() . '/splits-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testElevationInfoActionRequiresAuthentication(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);

        $this->request('GET', '/activity/' . $activity->getId() . '/elevation-info');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testElevationInfoActionRendersNotPossibleWhenNoLegacyRouteExists(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activity->getId() . '/elevation-info');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testElevationInfoActionWithUseCalculatedValueReturns404ForAnUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/999999999/elevation-info?use-calculated-value=true');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testElevationInfoActionWithUseCalculatedValueReturns404ForAnActivityBelongingToAnotherAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '/elevation-info?use-calculated-value=true');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testClimbScoreActionReturns404ForAnUnknownId(): void
    {
        $this->request('GET', '/activity/999999999/climb-score');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testClimbScoreActionRendersNotPossibleForAPublicActivityWithoutBeingLoggedIn(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: true);

        $this->request('GET', '/activity/' . $activity->getId() . '/climb-score');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testClimbScoreActionReturns404ForAPrivateActivityWithoutBeingLoggedIn(): void
    {
        $activity = $this->createTrainingForAccount($this->Account, isPublic: false);

        $this->request('GET', '/activity/' . $activity->getId() . '/climb-score');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testClimbScoreActionReturns404ForAPrivateActivityBelongingToAnotherLoggedInAccount(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $activity = $this->createTrainingForAccount($otherAccount, isPublic: false);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->request('GET', '/activity/' . $activity->getId() . '/climb-score');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testClimbScoreActionRendersNotPossibleForOwnActivityWithoutTrackdataOrRoute(): void
    {
        $activity = $this->createTrainingForAccount($this->Account);
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/activity/' . $activity->getId() . '/climb-score');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }
}
