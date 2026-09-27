<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class DefaultControllerTest extends AbstractFixturesAwareWebTestCase
{
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

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_encoder.generic');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->encodePassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function createHashGenerator(): UnsubscribeHashGenerator
    {
        return self::getContainer()->get(UnsubscribeHashGenerator::class);
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
