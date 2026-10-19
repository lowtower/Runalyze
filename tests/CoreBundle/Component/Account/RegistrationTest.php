<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Account;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class RegistrationTest extends KernelTestCase
{
    private const string PASSWORD = 'Pa$$w0rd';

    protected ObjectManager $EntityManager;

    protected AccountRepository $AccountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        self::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get()
            ->loadFixtures([]);

        $this->EntityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();
        $this->EntityManager->clear();

        $accountRepository = $this->EntityManager->getRepository(Account::class);
        $this->assertInstanceOf(AccountRepository::class, $accountRepository);
        $this->AccountRepository = $accountRepository;
    }

    public function testThatRegistrationProcessWorks(): void
    {
        $registration = $this->createRegistration();
        $registration->requireAccountActivation();

        $storedAccount = $this->registerAndReload($registration);

        $this->assertSame('foobar', $storedAccount->getUserIdentifier());
        $this->assertSame('foo@bar.com', $storedAccount->getMail());
        $this->assertNotNull($storedAccount->getActivationHash());
    }

    public function testRequireAccountActivationSetsRandomHash(): void
    {
        $account = $this->createAccount();

        $this->createRegistration($account)
            ->requireAccountActivation();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $account->getActivationHash());
    }

    public function testNewSaltHasThirtyTwoHexCharactersAndDiffersBetweenCalls(): void
    {
        $firstSalt = Registration::getNewSalt();
        $secondSalt = Registration::getNewSalt();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $firstSalt);
        $this->assertNotSame($firstSalt, $secondSalt);
    }

    public function testSetLocaleChangesLanguageOfAccount(): void
    {
        $account = $this->createAccount();

        $this->createRegistration($account)
            ->setLocale('de');

        $this->assertSame('de', $account->getLanguage());
    }

    public function testSetPasswordStoresHashThatMatchesTheGivenPassword(): void
    {
        $account = $this->createAccount();

        $this->createRegistration($account)
            ->setPassword(self::PASSWORD);

        $this->assertNotSame(self::PASSWORD, $account->getPassword());
        $this->assertTrue($this->getPasswordHasher()->isPasswordValid($account, self::PASSWORD));
        $this->assertFalse($this->getPasswordHasher()->isPasswordValid($account, 'wrong password'));
    }

    public function testRegisterAccountCreatesAllDefaultSports(): void
    {
        $storedAccount = $this->registerAndReload($this->createRegistration());

        /** @var SportRepository $sportRepository */
        $sportRepository = $this->EntityManager->getRepository(Sport::class);
        $this->assertInstanceOf(SportRepository::class, $sportRepository);

        $images = array_map(
            static fn (Sport $sport): string => $sport->getImg(),
            $sportRepository->findAllFor($storedAccount)
        );

        $this->assertEqualsCanonicalizing(['icons8-Running', 'icons8-Swimming', 'icons8-Regular-Biking', 'icons8-Yoga', 'icons8-Sports-Mode'], $images);
    }

    #[DataProvider('equipmentTypeNames')]
    public function testRegisterAccountCreatesDefaultEquipmentType(string $name): void
    {
        $storedAccount = $this->registerAndReload($this->createRegistration());

        $equipmentType = $this->EntityManager
            ->getRepository(EquipmentType::class)
            ->findOneBy([
                'name' => __($name),
                'account' => $storedAccount->getId(),
            ]);

        $this->assertInstanceOf(EquipmentType::class, $equipmentType);
    }

    public static function equipmentTypeNames(): iterable
    {
        yield 'shoes' => ['Shoes'];
        yield 'clothes' => ['Clothes'];
        yield 'bikes' => ['Bikes'];
    }

    public function testRegisteredSportsAndEquipmentTypeAreAvailableAfterRegistration(): void
    {
        $registration = $this->createRegistration();
        $this->registerAndReload($registration);

        $this->assertSame('icons8-Running', $registration->getRegisteredSportForRunning()->getImg());
        $this->assertSame('icons8-Regular-Biking', $registration->getRegisteredSportForCycling()->getImg());
        $this->assertInstanceOf(EquipmentType::class, $registration->getRegisteredEquipmentTypeClothes());
    }

    #[DataProvider('registeredDataGetters')]
    public function testGettersThrowLogicExceptionBeforeAccountWasRegistered(string $getter): void
    {
        $registration = $this->createRegistration();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('Account has to be registered first.');

        $registration->{$getter}();
    }

    public static function registeredDataGetters(): iterable
    {
        yield 'running sport' => ['getRegisteredSportForRunning'];
        yield 'cycling sport' => ['getRegisteredSportForCycling'];
        yield 'equipment type clothes' => ['getRegisteredEquipmentTypeClothes'];
    }

    /**
     * @param string[] $availableSportImages
     */
    #[DataProvider('incompleteSports')]
    public function testRegisterAccountThrowsLogicExceptionWhenDefaultSportIsMissing(array $availableSportImages, string $expectedMessage): void
    {
        $registration = $this->createRegistration(
            entityManager: $this->createEntityManagerWithRepositories(
                $this->createSportRepository($availableSportImages),
                $this->createEquipmentTypeRepository(null)
            )
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains($expectedMessage);

        $registration->registerAccount();
    }

    public static function incompleteSports(): iterable
    {
        $missingRunning = 'Default running sport could not be found for the new account.';
        $missingCycling = 'Default cycling sport could not be found for the new account.';

        yield 'no sport at all' => [[], $missingRunning];
        yield 'only unrelated sport' => [['icons8-Yoga'], $missingRunning];
        yield 'only cycling' => [['icons8-Regular-Biking'], $missingRunning];
        yield 'only running' => [['icons8-Running'], $missingCycling];
    }

    #[DataProvider('equipmentTypeNames')]
    public function testRegisterAccountThrowsLogicExceptionWhenEquipmentTypeIsMissing(string $missingName): void
    {
        $translatedName = __($missingName);

        $registration = $this->createRegistration(
            entityManager: $this->createEntityManagerWithRepositories(
                $this->createSportRepository(['icons8-Running', 'icons8-Regular-Biking']),
                $this->createEquipmentTypeRepository($translatedName)
            )
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('Equipment type "%s" could not be found for the new account.', $translatedName));

        $registration->registerAccount();
    }

    private function createAccount(): Account
    {
        $account = new Account();
        $account->setUsername('foobar');
        $account->setMail('foo@bar.com');

        return $account;
    }

    private function createRegistration(?Account $account = null, ?ObjectManager $entityManager = null): Registration
    {
        $registration = new Registration(
            $entityManager ?? $this->EntityManager,
            $this->getPasswordHasher()
        );
        $registration->setAccount($account ?? $this->createAccount());

        return $registration;
    }

    private function getPasswordHasher(): UserPasswordHasherInterface
    {
        $passwordHasher = self::getContainer()
            ->get('security.user_password_hasher');
        $this->assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);

        return $passwordHasher;
    }

    private function registerAndReload(Registration $registration): Account
    {
        $registration->setPassword(self::PASSWORD);
        $registeredAccount = $registration->registerAccount();

        $storedAccount = $this->AccountRepository->find($registeredAccount->getId());
        $this->assertInstanceOf(Account::class, $storedAccount);

        return $storedAccount;
    }

    /**
     * @param string[] $images
     */
    private function createSportRepository(array $images): SportRepository&Stub
    {
        $sports = array_map(
            static function (string $image): Sport {
                $sport = new Sport();
                $sport->setImg($image);

                return $sport;
            },
            $images
        );

        $repository = $this->createStub(SportRepository::class);
        $repository->method('findAllFor')
            ->willReturn($sports);

        return $repository;
    }

    private function createEquipmentTypeRepository(?string $missingName): ObjectRepository&Stub
    {
        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findOneBy')
            ->willReturnCallback(
                static fn (array $criteria): ?EquipmentType => $criteria['name'] === $missingName ? null : new EquipmentType()
            );

        return $repository;
    }

    private function createEntityManagerWithRepositories(
        SportRepository $sportRepository,
        ObjectRepository $equipmentTypeRepository
    ): ObjectManager&Stub {
        $entityManager = $this->createStub(ObjectManager::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(
                static fn (string $className): ObjectRepository => $className === Sport::class ? $sportRepository : $equipmentTypeRepository
            );

        return $entityManager;
    }
}
