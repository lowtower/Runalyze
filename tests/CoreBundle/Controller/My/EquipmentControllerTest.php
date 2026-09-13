<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

/**
 * @group requiresKernel
 * @group requiresClient
 */
class EquipmentControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    public function testOverviewAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/overview');

        $this->assertStatusCode(200, $client);
    }

    public function testCategoryAction(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/equipment/category/add');

        $this->assertStatusCode(200, $client);
    }
}
