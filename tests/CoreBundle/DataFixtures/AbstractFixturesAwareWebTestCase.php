<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class AbstractFixturesAwareWebTestCase extends WebTestCase
{
    use FixturesTrait;

    /**
     * @var Client
     */
    protected $Client;

    /**
     * @var ReferenceRepository
     */
    protected $Fixtures;

    /**
     * @var array|null
     */
    protected $FixtureClasses;

    /**
     * @var EntityManager
     */
    protected $EntityManager;

    /**
     * @var string
     */
    private $PreviousPhpTimezone;

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

        $this->Fixtures = $this->loadFixtures($this->FixtureClasses)
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

        if ($this->EntityManager !== null) {
            $this->EntityManager->close();
            $this->EntityManager = null;
        }
    }

    /**
     * @return Account
     */
    protected function getDefaultAccount()
    {
        $ref = $this->Fixtures->getReference('account-default');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Account')
            ->find($ref->getId());
    }

    /**
     * @return Sport
     */
    protected function getDefaultAccountsRunningSport()
    {
        $ref = $this->Fixtures->getReference('account-default.sport-running');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Sport')
            ->find($ref->getId());
    }

    /**
     * @return Sport
     */
    protected function getDefaultAccountsCyclingSport()
    {
        $ref = $this->Fixtures->getReference('account-default.sport-cycling');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Sport')
            ->find($ref->getId());
    }

    /**
     * @return EquipmentType
     */
    protected function getDefaultAccountsClothesType()
    {
        $ref = $this->Fixtures->getReference('account-default.equipment-type-clothes');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:EquipmentType')
            ->find($ref->getId());
    }

    /**
     * @return Account
     */
    protected function getEmptyAccount()
    {
        $ref = $this->Fixtures->getReference('account-empty');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Account')
            ->find($ref->getId());
    }

    /**
     * @param int|null $timestamp
     * @param int|float $duration
     * @param float|int|null $distance
     * @return Training
     */
    protected function getActivityForDefaultAccount(
        $timestamp = null,
        $duration = 3600,
        $distance = null,
        ?Sport $sport = null
    )
    {
        return (new Training())
            ->setS($duration)
            ->setTime($timestamp ?: time())
            ->setDistance($distance)
            ->setSport($sport ?: $this->getDefaultAccountsRunningSport())
            ->setAccount($this->getDefaultAccount());
    }
}
