<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use LogicException;
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

    protected ?ObjectManager $EntityManager = null;

    private string $PreviousPhpTimezone;

    private ?string $PreviousEnvironmentTimezone = null;

    private function rememberTimezone(): void
    {
        $this->PreviousPhpTimezone = date_default_timezone_get();
        $this->PreviousEnvironmentTimezone = getenv('TZ');
    }

    private function restoreTimezone(): void
    {
        if (! $this->PreviousEnvironmentTimezone) {
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

        $this->FixtureClasses ??= [
            LoadAccountData::class,
        ];

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class);
        $this->Fixtures = $databaseTool->get()
            ->withDatabaseCacheEnabled(false)
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

    /**
     * @throws LogicException if the manager is not a Doctrine ORM EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        if (! $this->EntityManager instanceof EntityManager) {
            throw new LogicException(sprintf(
                'Expected instance of %s, got %s.',
                EntityManager::class,
                get_debug_type($this->EntityManager)
            ));
        }

        return $this->EntityManager;
    }

    /**
     * Reloads a fixture reference through the EntityManager instead of returning the fixture
     * reference proxy directly, to avoid "A new entity was found through the relationship ...
     * that was not configured to cascade persist operations for entity" errors. Centralizes
     * what used to be four nearly identical getDefault*()/getEmpty*() method bodies.
     *
     * @template T of object
     * @param class-string<T> $entityClass
     * @return T
     */
    private function reloadFixtureReference(string $referenceName, string $entityClass): object
    {
        $reference = $this->Fixtures->getReference($referenceName);

        return $this->getEntityManager()
            ->getRepository($entityClass)
            ->find($reference->getId());
    }

    protected function getDefaultAccount(): Account
    {
        return $this->reloadFixtureReference('account-default', Account::class);
    }

    protected function getDefaultAccountsRunningSport(): Sport
    {
        return $this->reloadFixtureReference('account-default.sport-running', Sport::class);
    }

    protected function getDefaultAccountsCyclingSport(): Sport
    {
        return $this->reloadFixtureReference('account-default.sport-cycling', Sport::class);
    }

    protected function getDefaultAccountsClothesType(): EquipmentType
    {
        return $this->reloadFixtureReference('account-default.equipment-type-clothes', EquipmentType::class);
    }

    protected function getEmptyAccount(): Account
    {
        return $this->reloadFixtureReference('account-empty', Account::class);
    }

    protected function getActivityForDefaultAccount(
        ?int $timestamp = null,
        float $duration = 3600,
        ?float $distance = null,
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
