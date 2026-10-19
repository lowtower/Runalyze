<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

abstract class AbstractRepositoryTestCase extends AbstractFixturesAwareWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('RUNALYZE_DEBUG_SQL')) {
            $connection = $this->getEntityManager()
                ->getConnection();
            $platform = $connection->getDatabasePlatform();

            fwrite(STDERR, 'Entity Manager platform: ' . $platform::class . "\n");
            fwrite(STDERR, 'Entity Manager driver: ' . $connection->getDriver()::class . "\n");
        }
    }
}
