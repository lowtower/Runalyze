<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class InstallControllerTest extends AbstractFixturesAwareWebTestCase
{
    public function testInstallRoutesAreReachableInTheTestEnvironment(): void
    {
        $this->Client->request('GET', '/install/finish');

        $this->assertNotSame(404, $this->Client->getResponse()->getStatusCode());
    }

    public function testInstallActionRedirectsToFinishWhenInstallationWasAlreadySuccessful(): void
    {
        $this->Client->request('GET', '/install');
        $this->Client->getRequest()
            ->getSession()
            ->set('installer/successful', true);

        $this->Client->request('GET', '/install');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install/finish', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testStartActionRedirectsToInstallWhenInstallationIsNotYetKnownToBePossible(): void
    {
        $this->Client->request('GET', '/install/start');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testStartActionRedirectsToInstallWhenInstallationWasPreviouslyDeterminedNotPossible(): void
    {
        $this->Client->request('GET', '/install');
        $this->Client->getRequest()
            ->getSession()
            ->set('installer/possible', false);

        $this->Client->request('GET', '/install/start');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testStartActionRedirectsToFinishWhenInstallationWasAlreadySuccessful(): void
    {
        $this->Client->request('GET', '/install');
        $session = $this->Client->getRequest()
            ->getSession();
        $session->set('installer/possible', true);
        $session->set('installer/successful', true);

        $this->Client->request('GET', '/install/start');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install/finish', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testFinishActionRedirectsToInstallWhenInstallationWasNotYetSuccessful(): void
    {
        $this->Client->request('GET', '/install/finish');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testFinishActionShowsOutputWhenInstallationWasSuccessful(): void
    {
        $this->Client->request('GET', '/install');
        $session = $this->Client->getRequest()
            ->getSession();
        $session->set('installer/successful', true);
        $session->set('installer/output', 'some recorded installer output');

        $this->Client->request('GET', '/install/finish');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('some recorded installer output', (string) $this->Client->getResponse()->getContent());
    }
}
