<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class CallControllerTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('call_test_' . uniqid('', true))
            ->setMail('call_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function tinyPngDataUri(): string
    {
        $rawPngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return 'data:image/png;base64,' . base64_encode($rawPngBytes);
    }

    public function testDataBrowserActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/call.DataBrowser.display.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDataBrowserActionRendersForAuthenticatedUserWithoutLeakingASessionException(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/call.DataBrowser.display.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testGarminCommunicatorActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/call.garminCommunicator.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testGarminCommunicatorActionRendersForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/upload/garminCommunicator');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testSavePngActionRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/call/savePng.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSavePngActionReturns400WhenFilenameIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'image' => $this->tinyPngDataUri(),
        ]);

        $this->assertStatusCode(400, $this->Client);
    }

    public function testSavePngActionReturns400WhenImageIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'filename' => 'my export.png',
        ]);

        $this->assertStatusCode(400, $this->Client);
    }

    public function testSavePngActionReturnsTheDecodedImageWithCorrectHeadersForAValidSubmission(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/call/savePng.php', [
            'filename' => 'my export.png',
            'image' => $this->tinyPngDataUri(),
        ]);

        $this->assertStatusCode(200, $this->Client);

        $response = $this->Client->getResponse();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('my_export.png', $response->headers->get('Content-Disposition'));
        $this->assertSame(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            $response->getContent()
        );
    }

    public function testWindowConfigActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/settings');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowConfigActionRendersTheConfigTabsForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/settings');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'Runalyze.Overlay.removeClasses();',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testAjaxChangeConfigActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/ajax.change.Config.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testAjaxChangeConfigActionNoOpsWhenKeyIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testAjaxChangeConfigActionNoOpsForAnUnrecognizedKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/ajax.change.Config.php?key=some-unrecognized-key&value=1');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testWindowSearchActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/my/search');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowSearchActionRendersTheSearchFormForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/my/search');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString(
            'Search for activities',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testWindowsPlotSumDataActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/call/window.plotSumData.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testWindowsPlotSumDataActionRendersForAuthenticatedUserWithoutLeakingASessionException(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/call/window.plotSumData.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'Failed to start the session',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testLoginActionRendersWithoutRequiringAuthentication(): void
    {
        $this->Client->request('GET', '/call/login.php');

        $this->assertStatusCode(200, $this->Client);
    }
}
