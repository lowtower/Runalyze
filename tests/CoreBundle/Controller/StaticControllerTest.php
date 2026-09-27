<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class StaticControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    public function testDashboardHelpActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/dashboard/help');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDashboardHelpActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/dashboard/help');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testDashboardHelpCalculationsActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/dashboard/help-calculations');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testDashboardHelpCalculationsActionIsAccessibleForLoggedInUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/dashboard/help-calculations');

        $this->assertStatusCode(200, $this->Client);
    }
}
