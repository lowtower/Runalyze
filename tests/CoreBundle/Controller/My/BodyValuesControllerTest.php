<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class BodyValuesControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    public function testAddAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/add');

        $this->assertStatusCode(200, $client);
    }

    public function testTableAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/body-values/table');

        $this->assertStatusCode(200, $client);
    }
}
