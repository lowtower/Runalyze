<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use LogicException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

abstract class AbstractFixturesAwareWebTestCase extends WebTestCase
{
    protected const AUTHENTICATED_USERNAME = 'default';

    protected const AUTHENTICATED_PASSWORD = 'defaultPassword';

    private const int RESPONSE_EXCERPT_LENGTH = 2000;

    protected KernelBrowser $Client;

    protected ReferenceRepository $Fixtures;

    /**
     * @var array<int, class-string>|null
     */
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

    /**
     * Logs in through KernelBrowser::loginUser() and passes $firewallName explicitly. Without it,
     * loginUser()'s $firewallContext parameter defaults to "main", so the token ends up stored in
     * the session under "_security_main" instead of "_security_{$firewallName}" - Symfony's
     * ContextListener then never finds it again on the next request for firewalls not named "main"
     * (e.g. this project's "default" firewall), leaving every subsequent request unauthenticated.
     */
    public function loginClient(KernelBrowser $client, UserInterface $user, string $firewallName): void
    {
        $client->loginUser($user, $firewallName);
    }

    /**
     * Boots a fresh kernel and returns a new client for it.
     *
     * @param array<string, mixed> $server
     */
    protected function makeClient(array $server = []): KernelBrowser
    {
        static::ensureKernelShutdown();

        return static::createClient([], $server);
    }

    /**
     * Same as makeClient(), but authenticates via HTTP basic auth as the default fixture account.
     *
     * @param array<string, mixed> $server
     */
    protected function makeAuthenticatedClient(array $server = []): KernelBrowser
    {
        return $this->makeClient(array_merge($server, [
            'PHP_AUTH_USER' => self::AUTHENTICATED_USERNAME,
            'PHP_AUTH_PW' => self::AUTHENTICATED_PASSWORD,
        ]));
    }

    public static function assertStatusCode(int $expectedStatusCode, KernelBrowser $client): void
    {
        $response = $client->getResponse();

        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode(),
            self::describeResponse($response)
        );
    }

    public static function isSuccessful(Response $response, bool $success = true): void
    {
        $description = self::describeResponse($response);

        if ($success) {
            self::assertTrue($response->isSuccessful(), 'The response was not successful: ' . $description);
        } else {
            self::assertFalse($response->isSuccessful(), 'The response was successful: ' . $description);
        }
    }

    private static function describeResponse(Response $response): string
    {
        $content = (string) preg_replace('/\x1b\[[0-9;]*m/', '', (string) $response->getContent());

        try {
            $title = new Crawler($content)->filter('title');

            if (\count($title) > 0) {
                return sprintf('[%d] %s', $response->getStatusCode(), trim($title->text()));
            }
        } catch (Throwable) {
        }

        return sprintf('[%d] %s', $response->getStatusCode(), substr($content, 0, self::RESPONSE_EXCERPT_LENGTH));
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
     * @param array<string, mixed> $attributes
     */
    protected function createSessionWithAttributes(array $attributes): void
    {
        $sessionFactory = static::getContainer()->get('session.factory');
        self::assertInstanceOf(SessionFactoryInterface::class, $sessionFactory);

        $session = $sessionFactory->createSession();

        foreach ($attributes as $name => $value) {
            $session->set($name, $value);
        }

        $session->save();

        $this->Client->getCookieJar()
            ->set(new Cookie($session->getName(), $session->getId()));
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
     *
     * @throws LogicException if the referenced entity does not exist in the database
     */
    private function reloadFixtureReference(string $referenceName, string $entityClass): object
    {
        $reference = $this->Fixtures->getReference($referenceName, $entityClass);

        $entity = $this->getEntityManager()
            ->getRepository($entityClass)
            ->find($reference->getId());

        if (! $entity instanceof $entityClass) {
            throw new LogicException(sprintf(
                'Fixture reference "%s" (%s with id %s) could not be reloaded from the database.',
                $referenceName,
                $entityClass,
                (string) $reference->getId()
            ));
        }

        return $entity;
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
        return new Training()
            ->setS($duration)
            ->setTime($timestamp ?: time())
            ->setDistance($distance)
            ->setSport($sport ?: $this->getDefaultAccountsRunningSport())
            ->setAccount($this->getDefaultAccount());
    }
}
