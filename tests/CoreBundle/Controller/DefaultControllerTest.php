<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Model\Account\UserRole;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class DefaultControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const string KNOWN_PASSWORD = 'irrelevant-for-this-test';

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = (new Account())
            ->setUsername('default_test_' . uniqid('', true))
            ->setMail('default_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function registerDisabledAccount(): Account
    {
        $account = (new Account())
            ->setUsername('default_test_' . uniqid('', true))
            ->setMail('default_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $registration->requireAccountActivation();

        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function registerAdminAccount(): Account
    {
        $account = (new Account())
            ->setUsername('default_test_admin_' . uniqid('', true))
            ->setMail('default_test_admin_' . uniqid('', true) . '@example.test')
            ->setRole(UserRole::ROLE_ADMIN);

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->hashPassword($account, self::KNOWN_PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function createHashGenerator(): UnsubscribeHashGenerator
    {
        return self::getContainer()->get(UnsubscribeHashGenerator::class);
    }

    private function allowSupportFor(Account $account): void
    {
        $account->setAllowSupport(true);

        $this->getEntityManager()
            ->persist($account);
        $this->getEntityManager()
            ->flush();
    }

    public function testIndexActionRedirectsToLoginWhenNotAuthenticated(): void
    {
        $this->Client->request('GET', '/');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/en/login', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testIndexActionRedirectsToDashboardWhenAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginActionRendersTheLoginFormWhenNotAuthenticated(): void
    {
        $this->Client->request('GET', '/en/login');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testLoginActionRedirectsToDashboardWhenAlreadyAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/en/login');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginActionRendersTheAjaxTemplateForAnXmlHttpRequest(): void
    {
        $this->Client->request('GET', '/en/login', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testLoginCheckWithValidCredentialsLogsInAndRedirectsToDashboard(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));

        $this->Client->followRedirect();

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginCheckWithWrongPasswordRedirectsBackToLoginWithError(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => 'this-is-not-the-correct-password',
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithUnknownUsernameRedirectsBackToLoginWithError(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => 'this-user-does-not-exist',
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithDisabledAccountRedirectsBackToLoginWithError(): void
    {
        $disabledAccount = $this->registerDisabledAccount();

        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $disabledAccount->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithoutCsrfTokenIsRejected(): void
    {
        $this->Client->request('POST', '/en/login_check', [
            '_username' => $this->Account->getUsername(),
            '_password' => self::KNOWN_PASSWORD,
        ]);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testLoginCheckWithInvalidCsrfTokenIsRejected(): void
    {
        $this->Client->request('POST', '/en/login_check', [
            '_username' => $this->Account->getUsername(),
            '_password' => self::KNOWN_PASSWORD,
            '_csrf_token' => 'not-a-valid-csrf-token',
        ]);

        $this->assertStatusCode(302, $this->Client);

        $this->Client->followRedirect();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('class="error"', (string) $this->Client->getResponse()->getContent());
    }

    public function testSuccessfulLoginWithRememberMeSetsAnAdditionalCookie(): void
    {
        $crawler = $this->Client->request('GET', '/en/login');
        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => self::KNOWN_PASSWORD,
            ]);

        $this->Client->submit($form);

        $this->assertStatusCode(302, $this->Client);

        $cookieNames = array_map(
            static fn (\Symfony\Component\BrowserKit\Cookie $cookie): string => $cookie->getName(),
            $this->Client->getCookieJar()
                ->all()
        );

        $this->assertNotEmpty(array_diff($cookieNames, ['MOCKSESSID']));
    }

    public function testSwitchUserAllowsAdminToImpersonateAnotherUser(): void
    {
        $this->allowSupportFor($this->Account);

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $tokenStorage = self::getContainer()->get('security.token_storage');
        $impersonatedUser = $tokenStorage->getToken()
            ->getUser();

        $this->assertInstanceOf(Account::class, $impersonatedUser);
        $this->assertSame($this->Account->getUsername(), $impersonatedUser->getUserIdentifier());
    }

    public function testSwitchUserExitReturnsToOriginalUser(): void
    {
        $this->allowSupportFor($this->Account);

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $this->Client->request('GET', '/dashboard?_switch_user=_exit');
        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/dashboard', $this->Client->getResponse()->headers->get('Location'));

        $tokenStorage = self::getContainer()->get('security.token_storage');
        $currentUser = $tokenStorage->getToken()
            ->getUser();

        $this->assertInstanceOf(Account::class, $currentUser);
        $this->assertSame($admin->getUsername(), $currentUser->getUserIdentifier());
    }

    public function testSwitchUserIsDeniedForNonAdminUser(): void
    {
        $otherAccount = $this->registerUniqueAccount();
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $otherAccount->getUsername());

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSwitchUserIsDeniedWhenTargetAccountDoesNotAllowSupport(): void
    {
        $this->assertFalse($this->Account->getAllowSupport());

        $admin = $this->registerAdminAccount();
        $this->loginClient($this->Client, $admin, 'default');

        $this->Client->request('GET', '/dashboard?_switch_user=' . $this->Account->getUsername());

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testLogoutActionRedirectsToLogin(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/logout');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testRegisterActionRendersTheRegistrationFormWhenRegistrationIsEnabled(): void
    {
        $this->Client->request('GET', '/en/register');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('name="registration"', (string) $this->Client->getResponse()->getContent());
    }

    public function testDashboardActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/dashboard');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDashboardActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDashboardActionRendersTheLegacyDashboardContentForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/dashboard');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="data-browser"', (string) $this->Client->getResponse()->getContent());
        $this->assertStringContainsString('id="statistics"', (string) $this->Client->getResponse()->getContent());
    }

    public function testPluginActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/plugin/SomePlugin/index.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginActionRejectsPathTraversalInPluginSegment(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/plugin/..%2f..%2f..%2fetc/passwd');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testPluginActionRejectsPathTraversalInFileSegment(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/plugin/SomePlugin/..%2f..%2fclass.Cache.php');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testIndexPhpActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/index.php', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testIndexPhpActionRedirectsToBaseUrlForANonXhrRequestWhenAuthenticated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/index.php');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testLoginPhpActionRedirectsToDashboard(): void
    {
        $this->Client->request('GET', '/login.php');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/dashboard', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testUnsubscribeMailActionShowsInfoPageForAValidHash(): void
    {
        $hash = $this->createHashGenerator()
            ->generate($this->Account->getUsername());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/' . $hash);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailActionShowsFailurePageForAnInvalidHash(): void
    {
        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/not-a-valid-hash');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailActionShowsFailurePageForAnUnknownMailAddress(): void
    {
        $hash = $this->createHashGenerator()
            ->generate('someone-who-does-not-exist');

        $this->Client->request('GET', '/unsubscribe/unknown@example.test/' . $hash);

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUnsubscribeMailConfirmActionDisablesMailsForAValidHash(): void
    {
        $this->assertTrue($this->Account->getAllowMails());

        $hash = $this->createHashGenerator()
            ->generate($this->Account->getUsername());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/' . $hash . '/confirm');

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());
        $this->assertFalse($reloadedAccount->getAllowMails());
    }

    public function testUnsubscribeMailConfirmActionDoesNotDisableMailsForAnInvalidHash(): void
    {
        $this->assertTrue($this->Account->getAllowMails());

        $this->Client->request('GET', '/unsubscribe/' . urlencode($this->Account->getMail()) . '/not-a-valid-hash/confirm');

        $this->assertStatusCode(200, $this->Client);

        $this->getEntityManager()
            ->clear();
        $reloadedAccount = $this->getEntityManager()
            ->getRepository(Account::class)->find($this->Account->getId());
        $this->assertTrue($reloadedAccount->getAllowMails());
    }

    public function testFeedbackActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/feedback');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testFeedbackActionReturns404WhenNoRecipientIsConfigured(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/feedback');

        $this->assertStatusCode(404, $this->Client);
    }
}
