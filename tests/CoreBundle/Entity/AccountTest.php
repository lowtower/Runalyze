<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Model\Account\UserRole;
use Runalyze\Profile\Athlete\Gender;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AccountTest extends TestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        $this->Account = new Account();
    }

    private function serializeAndRestore(): Account
    {
        $restored = unserialize(serialize($this->Account));

        $this->assertInstanceOf(Account::class, $restored);

        return $restored;
    }

    private function readPlainPasswordProperty(Account $account): ?string
    {
        return new ReflectionProperty(Account::class, 'plainPassword')->getValue($account);
    }

    public function testDefaultValues(): void
    {
        $this->assertNotNull($this->Account->getSalt());

        $this->assertNull($this->Account->getChangepwHash());
        $this->assertNull($this->Account->getChangepwTimelimit());
        $this->assertNull($this->Account->getActivationHash());
        $this->assertNull($this->Account->getDeletionHash());
        $this->assertFalse($this->Account->knowsGender());
        $this->assertFalse($this->Account->knowsBirthYear());
        $this->assertNull($this->Account->getAge());
    }

    public function testMailAndPlainPasswordAreEmptyForNewAccount(): void
    {
        $this->assertSame('', $this->Account->getMail());
        $this->assertSame('', $this->Account->getPlainPassword());
    }

    public function testPlainPasswordIsNotSetOnNewAccountSoThatLengthValidationSkipsIt(): void
    {
        $this->assertNull($this->readPlainPasswordProperty($this->Account));
    }

    public function testPlainPasswordCanBeSetAndRead(): void
    {
        $this->Account->setPlainPassword('plain-secret');

        $this->assertSame('plain-secret', $this->Account->getPlainPassword());
    }

    public function testAllowMailsDefaultsToTrue(): void
    {
        $this->assertTrue($this->Account->getAllowMails());
    }

    public function testNewAccountHasSaltOfSixtyFourHexCharacters(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->Account->getSalt());
    }

    public function testSetNewSaltReplacesSalt(): void
    {
        $previousSalt = $this->Account->getSalt();

        $this->Account->setNewSalt();

        $this->assertNotSame($previousSalt, $this->Account->getSalt());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->Account->getSalt());
    }

    public function testRegisterdateAndLastActionAreSetByConstructor(): void
    {
        $before = time();
        $account = new Account();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $account->getRegisterdate());
        $this->assertLessThanOrEqual($after, $account->getRegisterdate());
        $this->assertGreaterThanOrEqual($before, $account->getLastAction());
        $this->assertLessThanOrEqual($after, $account->getLastAction());
    }

    public function testSetLastActionWithoutArgumentUsesCurrentTime(): void
    {
        $before = time();
        $this->Account->setLastAction();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $this->Account->getLastAction());
        $this->assertLessThanOrEqual($after, $this->Account->getLastAction());
    }

    public function testSetLastActionStoresGivenTimestamp(): void
    {
        $this->Account->setLastAction(1_700_000_000);

        $this->assertSame(1_700_000_000, $this->Account->getLastAction());
    }

    public function testSettersReturnTheSameAccountForChaining(): void
    {
        $this->assertSame($this->Account, $this->Account->setUsername('chained'));
        $this->assertSame($this->Account, $this->Account->setMail('chained@example.test'));
        $this->assertSame($this->Account, $this->Account->setLanguage('de'));
        $this->assertSame($this->Account, $this->Account->setRole(UserRole::ROLE_ADMIN));
    }

    public function testUserIdentifierIsTheUsername(): void
    {
        $this->Account->setUsername('johndoe');

        $this->assertSame('johndoe', $this->Account->getUserIdentifier());
        $this->assertSame('johndoe', $this->Account->getUsername());
    }

    public function testRestoredAccountHasNoPlainPassword(): void
    {
        $this->Account->setPlainPassword('plain-secret');

        $restored = $this->serializeAndRestore();

        $this->assertSame('', $restored->getPlainPassword());
    }

    public function testRestoredAccountLeavesPlainPasswordUnsetSoThatLengthValidationSkipsIt(): void
    {
        $this->Account->setPlainPassword('plain-secret');

        $restored = $this->serializeAndRestore();

        $this->assertNull($this->readPlainPasswordProperty($restored));
    }

    public function testSerializingDoesNotChangePlainPasswordOfTheOriginalAccount(): void
    {
        $this->Account->setPlainPassword('plain-secret');

        serialize($this->Account);

        $this->assertSame('plain-secret', $this->Account->getPlainPassword());
    }

    public function testChangePasswordHash(): void
    {
        $this->Account->setNewChangePasswordHash();

        $this->assertNotNull($this->Account->getChangepwHash());
        $this->assertNotNull($this->Account->getChangepwTimelimit());

        $this->Account->removeChangePasswordHash();

        $this->assertNull($this->Account->getChangepwHash());
        $this->assertNull($this->Account->getChangepwTimelimit());
    }

    public function testNewChangePasswordHashIsValidForOneDay(): void
    {
        $before = time();
        $this->Account->setNewChangePasswordHash();
        $after = time();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $this->Account->getChangepwHash());
        $this->assertGreaterThanOrEqual($before + 86400, $this->Account->getChangepwTimelimit());
        $this->assertLessThanOrEqual($after + 86400, $this->Account->getChangepwTimelimit());
    }

    public function testActivationHash(): void
    {
        $this->Account->setNewActivationHash();

        $this->assertNotNull($this->Account->getActivationHash());

        $this->Account->removeActivationHash();

        $this->assertNull($this->Account->getActivationHash());
    }

    public function testNewActivationHashAndNewDeletionHashHaveThirtyTwoHexCharacters(): void
    {
        $this->Account->setNewActivationHash();
        $this->Account->setNewDeletionHash();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $this->Account->getActivationHash());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $this->Account->getDeletionHash());
    }

    #[DataProvider('activationHashes')]
    public function testIsEnabledDependsOnActivationHash(?string $activationHash, bool $expectedEnabled): void
    {
        $this->Account->setActivationHash($activationHash);

        $this->assertSame($expectedEnabled, $this->Account->isEnabled());
    }

    public static function activationHashes(): iterable
    {
        yield 'no hash' => [null, true];
        yield 'empty hash' => ['', true];
        yield 'hash' => ['a1b2c3', false];
        yield 'zero as text is a hash' => ['0', false];
    }

    #[DataProvider('hashLengthsInBytes')]
    public function testGetRandomHashHasTwoHexCharactersPerByte(int $bytes): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{' . ($bytes * 2) . '}$/', Account::getRandomHash($bytes));
    }

    public static function hashLengthsInBytes(): iterable
    {
        yield 'one byte' => [1];
        yield 'sixteen bytes' => [16];
        yield 'thirty-two bytes' => [32];
    }

    public function testGetRandomHashUsesSixteenBytesByDefaultAndDiffersBetweenCalls(): void
    {
        $firstHash = Account::getRandomHash();

        $this->assertSame(32, strlen($firstHash));
        $this->assertNotSame($firstHash, Account::getRandomHash());
    }

    public function testGender(): void
    {
        $this->Account->setGender(Gender::FEMALE);

        $this->assertTrue($this->Account->knowsGender());
        $this->assertTrue($this->Account->isFemale());
        $this->assertFalse($this->Account->isMale());

        $this->Account->setGender(Gender::MALE);

        $this->assertTrue($this->Account->knowsGender());
        $this->assertFalse($this->Account->isFemale());
        $this->assertTrue($this->Account->isMale());
    }

    public function testGenderNoneIsNotKnown(): void
    {
        $this->Account->setGender(Gender::FEMALE);
        $this->Account->setGender(Gender::NONE);

        $this->assertFalse($this->Account->knowsGender());
        $this->assertFalse($this->Account->isFemale());
        $this->assertFalse($this->Account->isMale());
    }

    public function testAge(): void
    {
        $this->Account->setBirthyear((int) date('Y') - 42);

        $this->assertTrue($this->Account->knowsBirthYear());
        $this->assertSame(42, $this->Account->getAge());
    }

    public function testRemovingBirthyearRemovesAge(): void
    {
        $this->Account->setBirthyear(1980);
        $this->Account->setBirthyear(null);

        $this->assertFalse($this->Account->knowsBirthYear());
        $this->assertNull($this->Account->getAge());
    }

    public function testSportsCollectionIsInitializedByConstructor(): void
    {
        $this->assertTrue($this->Account->getSports()->isEmpty());
    }

    public function testActivityTypesCollectionIsInitializedByConstructor(): void
    {
        $this->assertTrue($this->Account->getActivityTypes()->isEmpty());
    }

    public function testTagsEquipmentAndEquipmentTypesCollectionsAreInitializedByConstructor(): void
    {
        $this->assertTrue($this->Account->getTags()->isEmpty());
        $this->assertTrue($this->Account->getEquipment()->isEmpty());
        $this->assertTrue($this->Account->getEquipmentTypes()->isEmpty());
    }

    public function testRoleDefaultsToRoleUser(): void
    {
        $this->assertSame([UserRole::getRoleName(UserRole::ROLE_USER)], $this->Account->getRoles());
    }

    public function testSetRoleChangesRoles(): void
    {
        $this->Account->setRole(UserRole::ROLE_ADMIN);

        $this->assertSame(UserRole::ROLE_ADMIN, $this->Account->getRole());
        $this->assertSame(['ROLE_ADMIN'], $this->Account->getRoles());
    }

    public function testGetRolesThrowsInvalidArgumentExceptionForUnknownRole(): void
    {
        $this->Account->setRole(99);

        $this->expectException(InvalidArgumentException::class);

        $this->Account->getRoles();
    }

    public function testAllowSupportDefaultsToFalse(): void
    {
        $this->assertFalse($this->Account->getAllowSupport());
    }

    public function testSerializingAnAccountWithoutMailDoesNotThrow(): void
    {
        $this->Account->setUsername('no-mail-account');

        $serialized = serialize($this->Account);

        $this->assertIsString($serialized);
    }

    public function testUnserializingAnAccountThatWasSerializedWithoutMailRestoresEmptyMail(): void
    {
        $this->Account->setUsername('no-mail-account');

        $restored = $this->serializeAndRestore();

        $this->assertSame('', $restored->getMail());
    }

    public function testUnserializeWithoutDataFallsBackToDefaults(): void
    {
        $account = new Account();

        $account->__unserialize([]);

        $this->assertNull($account->getId());
        $this->assertSame('', $account->getUsername());
        $this->assertSame('', $account->getMail());
        $this->assertSame('', $account->getPassword());
        $this->assertSame('', $account->getSalt());
        $this->assertNull($account->getActivationHash());
        $this->assertSame('en', $account->getLanguage());
        $this->assertSame(['ROLE_USER'], $account->getRoles());
        $this->assertFalse($account->getAllowSupport());
    }

    public function testUnserializedAccountHasInitializedCollections(): void
    {
        $restored = $this->serializeAndRestore();

        $this->assertTrue($restored->getSports()->isEmpty());
        $this->assertTrue($restored->getActivityTypes()->isEmpty());
        $this->assertTrue($restored->getTags()->isEmpty());
        $this->assertTrue($restored->getEquipment()->isEmpty());
        $this->assertTrue($restored->getEquipmentTypes()->isEmpty());
    }

    public function testSerializationContainsOnlyDataNeededToIdentifyAndAuthenticate(): void
    {
        $this->assertSame(
            ['id', 'username', 'mail', 'password', 'salt', 'activationHash', 'language', 'role', 'allowSupport'],
            array_keys($this->Account->__serialize())
        );
    }

    public function testSerializationDoesNotContainPlainPassword(): void
    {
        $this->Account->setPlainPassword('plain-secret-that-must-not-be-stored');

        $this->assertStringNotContainsString('plain-secret-that-must-not-be-stored', serialize($this->Account));
    }

    public function testSerializationRoundTripPreservesIdUsernameAndMail(): void
    {
        $this->Account->setUsername('round-trip-user');
        $this->Account->setMail('round-trip@example.test');

        $restored = $this->serializeAndRestore();

        $this->assertSame('round-trip-user', $restored->getUsername());
        $this->assertSame('round-trip@example.test', $restored->getMail());
    }

    public function testSerializationRoundTripPreservesPasswordAndSalt(): void
    {
        $this->Account->setPassword('some-hashed-password');
        $this->Account->setSalt('some-salt');

        $restored = $this->serializeAndRestore();

        $this->assertSame('some-hashed-password', $restored->getPassword());
        $this->assertSame('some-salt', $restored->getSalt());
    }

    public function testSerializationRoundTripPreservesActivationHash(): void
    {
        $this->Account->setActivationHash('some-activation-hash');

        $restored = $this->serializeAndRestore();

        $this->assertSame('some-activation-hash', $restored->getActivationHash());
    }

    public function testSerializationRoundTripPreservesLanguage(): void
    {
        $this->Account->setLanguage('de');

        $restored = $this->serializeAndRestore();

        $this->assertSame('de', $restored->getLanguage());
    }

    public function testSerializationRoundTripPreservesAdminRole(): void
    {
        $this->Account->setRole(UserRole::ROLE_ADMIN);

        $restored = $this->serializeAndRestore();

        $this->assertSame([UserRole::getRoleName(UserRole::ROLE_ADMIN)], $restored->getRoles());
    }

    public function testSerializationRoundTripPreservesAllowSupportFlag(): void
    {
        $this->Account->setAllowSupport(true);

        $restored = $this->serializeAndRestore();

        $this->assertTrue($restored->getAllowSupport());
    }

    public function testSerializationRoundTripPreservesIdAfterBeingSet(): void
    {
        $reflection = new ReflectionProperty(Account::class, 'id');
        $reflection->setValue($this->Account, 42);

        $restored = $this->serializeAndRestore();

        $this->assertSame(42, $restored->getId());
    }

    private function createAccount(
        int $id = 1,
        string $username = 'same-user',
        string $password = 'same-password',
        string $salt = 'same-salt',
        int $role = UserRole::ROLE_USER
    ): Account {
        $account = new Account()
            ->setUsername($username)
            ->setPassword($password)
            ->setSalt($salt)
            ->setRole($role);

        new ReflectionProperty(Account::class, 'id')->setValue($account, $id);

        return $account;
    }

    public function testAccountImplementsEquatableInterface(): void
    {
        $this->assertInstanceOf(EquatableInterface::class, $this->Account);
    }

    public function testAccountIsEqualToAnotherAccountWithTheSameData(): void
    {
        $this->assertTrue($this->createAccount()->isEqualTo($this->createAccount()));
    }

    public function testAccountIsEqualToItself(): void
    {
        $account = $this->createAccount();

        $this->assertTrue($account->isEqualTo($account));
    }

    public function testAccountIsEqualToItsOwnSerializedAndRestoredCopy(): void
    {
        $this->Account = $this->createAccount(id: 42, role: UserRole::ROLE_ADMIN);

        $this->assertTrue($this->Account->isEqualTo($this->serializeAndRestore()));
    }

    public function testAccountIsNotEqualWhenTheIdDiffers(): void
    {
        $this->assertFalse($this->createAccount(id: 1)->isEqualTo($this->createAccount(id: 2)));
    }

    public function testAccountIsNotEqualWhenTheUsernameDiffers(): void
    {
        $this->assertFalse($this->createAccount(username: 'user-a')->isEqualTo($this->createAccount(username: 'user-b')));
    }

    public function testAccountIsNotEqualWhenThePasswordDiffers(): void
    {
        $this->assertFalse($this->createAccount(password: 'old-hash')->isEqualTo($this->createAccount(password: 'new-hash')));
    }

    public function testAccountIsNotEqualWhenTheSaltDiffers(): void
    {
        $this->assertFalse($this->createAccount(salt: 'old-salt')->isEqualTo($this->createAccount(salt: 'new-salt')));
    }

    public function testAccountIsNotEqualWhenTheRoleDiffers(): void
    {
        $this->assertFalse(
            $this->createAccount(role: UserRole::ROLE_USER)->isEqualTo($this->createAccount(role: UserRole::ROLE_ADMIN))
        );
    }

    public function testAccountIsStillEqualWhenOnlyUnrelatedDataDiffers(): void
    {
        $original = $this->createAccount();
        $changed = $this->createAccount();
        $changed->setName('Other Name')
            ->setLastAction(1_700_000_000)
            ->setAllowMails(false);

        $this->assertTrue($original->isEqualTo($changed));
    }

    public function testAccountIsNotEqualToAnotherUserType(): void
    {
        $otherUser = $this->createStub(UserInterface::class);

        $this->assertFalse($this->createAccount()->isEqualTo($otherUser));
    }
}
