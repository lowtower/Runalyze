<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class AbstractFixturesAwareWebTestCase extends WebTestCase
{
    protected Client $Client;

    protected ReferenceRepository $Fixtures;

    protected ?array $FixtureClasses = null;

    protected ?EntityManager $EntityManager = null;

    private string $PreviousPhpTimezone;

    /**
     * @var string|false
     */
    private $PreviousEnvironmentTimezone;

    private function rememberTimezone()
    {
        $this->PreviousPhpTimezone = date_default_timezone_get();
        $this->PreviousEnvironmentTimezone = getenv('TZ');
    }

    private function restoreTimezone()
    {
        if ($this->PreviousEnvironmentTimezone === false) {
            putenv('TZ');
        } else {
            putenv('TZ=' . $this->PreviousEnvironmentTimezone);
        }

        date_default_timezone_set($this->PreviousPhpTimezone);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rememberTimezone();

        if ($this->FixtureClasses === null) {
            $this->FixtureClasses = [
                LoadAccountData::class,
            ];
        }

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class);
        $this->Fixtures = $databaseTool->get()
            ->loadFixtures($this->FixtureClasses)
            ->getReferenceRepository();

        $this->Client = $this->getContainer()
            ->get('test.client');
        $this->Client->disableReboot();

        $this->EntityManager = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->EntityManager->clear();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $this->restoreTimezone();
        }

        if ($this->EntityManager instanceof EntityManager) {
            $this->EntityManager->close();
            $this->EntityManager = null;
        }
    }

    protected function getDefaultAccount(): Account
    {
        $ref = $this->Fixtures->getReference('account-default');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository(Account::class)
            ->find($ref->getId());
    }

    protected function getDefaultAccountsRunningSport(): Sport
    {
        $ref = $this->Fixtures->getReference('account-default.sport-running');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository(Sport::class)
            ->find($ref->getId());
    }

    protected function getDefaultAccountsCyclingSport(): Sport
    {
        $ref = $this->Fixtures->getReference('account-default.sport-cycling');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository(Sport::class)
            ->find($ref->getId());
    }

    protected function getDefaultAccountsClothesType(): EquipmentType
    {
        $ref = $this->Fixtures->getReference('account-default.equipment-type-clothes');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository(EquipmentType::class)
            ->find($ref->getId());
    }

    protected function getEmptyAccount(): Account
    {
        $ref = $this->Fixtures->getReference('account-empty');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository(Account::class)
            ->find($ref->getId());
    }

    /**
     * @param int|float $duration
     * @param float|int|null $distance
     */
    protected function getActivityForDefaultAccount(
        ?int $timestamp = null,
        $duration = 3600,
        $distance = null,
        ?Sport $sport = null
    ): Training {
        return (new Training())
            ->setS($duration)
            ->setTime($timestamp ?: time())
            ->setDistance($distance)
            ->setSport($sport ?: $this->getDefaultAccountsRunningSport())
            ->setAccount($this->getDefaultAccount());
    }
}
