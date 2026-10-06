<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class InstallControllerTest extends AbstractFixturesAwareWebTestCase
{
    public function testInstallRoutesAreReachableInTheTestEnvironment(): void
    {
        $this->Client->request('GET', '/install/finish');

        $this->assertNotSame(Response::HTTP_NOT_FOUND, $this->Client->getResponse()->getStatusCode());
    }

    public function testInstallActionRedirectsToFinishWhenInstallationWasAlreadySuccessful(): void
    {
        $this->createSessionWithAttributes([
            'installer/successful' => true,
        ]);

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
        $this->createSessionWithAttributes([
            'installer/possible' => false,
        ]);

        $this->Client->request('GET', '/install/start');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testStartActionRedirectsToInstallWhenOnlySuccessfulFlagIsSet(): void
    {
        $this->createSessionWithAttributes([
            'installer/successful' => true,
        ]);

        $this->Client->request('GET', '/install/start');

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('/install', $this->Client->getResponse()->headers->get('Location'));
    }

    public function testStartActionRedirectsToFinishWhenInstallationWasAlreadySuccessful(): void
    {
        $this->createSessionWithAttributes([
            'installer/possible' => true,
            'installer/successful' => true,
        ]);

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
        $this->createSessionWithAttributes([
            'installer/successful' => true,
            'installer/output' => 'some recorded installer output',
        ]);

        $this->Client->request('GET', '/install/finish');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('some recorded installer output', (string) $this->Client->getResponse()->getContent());
    }

    public function testFinishActionIsShownWithoutOutputWhenNoOutputWasRecorded(): void
    {
        $this->createSessionWithAttributes([
            'installer/successful' => true,
        ]);

        $this->Client->request('GET', '/install/finish');

        $this->assertStatusCode(200, $this->Client);
    }
}
