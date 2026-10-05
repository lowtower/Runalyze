<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Model\Account\UserRole;
use Runalyze\Profile\Athlete\Gender;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AccountTest extends \PHPUnit\Framework\TestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        $this->Account = new Account();
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

    public function testChangePasswordHash(): void
    {
        $this->Account->setNewChangePasswordHash();

        $this->assertNotNull($this->Account->getChangepwHash());
        $this->assertNotNull($this->Account->getChangepwTimelimit());

        $this->Account->removeChangePasswordHash();

        $this->assertNull($this->Account->getChangepwHash());
        $this->assertNull($this->Account->getChangepwTimelimit());
    }

    public function testActivationHash(): void
    {
        $this->Account->setNewActivationHash();

        $this->assertNotNull($this->Account->getActivationHash());

        $this->Account->removeActivationHash();

        $this->assertNull($this->Account->getActivationHash());
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

    public function testAge(): void
    {
        $this->Account->setBirthyear((int) date('Y') - 42);

        $this->assertTrue($this->Account->knowsBirthYear());
        $this->assertEquals(42, $this->Account->getAge());
    }

    public function testSportsCollectionIsInitializedByConstructor(): void
    {
        $this->assertTrue($this->Account->getSports()->isEmpty());
    }

    public function testActivityTypesCollectionIsInitializedByConstructor(): void
    {
        $this->assertTrue($this->Account->getActivityTypes()->isEmpty());
    }

    public function testRoleDefaultsToRoleUser(): void
    {
        $this->assertSame([UserRole::getRoleName(UserRole::ROLE_USER)], $this->Account->getRoles());
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

    public function testUnserializingAnAccountThatWasSerializedWithoutMailLeavesMailUninitialized(): void
    {
        $this->Account->setUsername('no-mail-account');

        $restored = unserialize(serialize($this->Account));

        $this->assertInstanceOf(Account::class, $restored);
        $this->expectException(\Error::class);

        $restored->getMail();
    }

    public function testSerializationRoundTripPreservesIdUsernameAndMail(): void
    {
        $this->Account->setUsername('round-trip-user');
        $this->Account->setMail('round-trip@example.test');

        $restored = unserialize(serialize($this->Account));

        $this->assertSame('round-trip-user', $restored->getUsername());
        $this->assertSame('round-trip@example.test', $restored->getMail());
    }

    public function testSerializationRoundTripPreservesPasswordAndSalt(): void
    {
        $this->Account->setPassword('some-hashed-password');
        $this->Account->setSalt('some-salt');

        $restored = unserialize(serialize($this->Account));

        $this->assertSame('some-hashed-password', $restored->getPassword());
        $this->assertSame('some-salt', $restored->getSalt());
    }

    public function testSerializationRoundTripPreservesActivationHash(): void
    {
        $this->Account->setActivationHash('some-activation-hash');

        $restored = unserialize(serialize($this->Account));

        $this->assertSame('some-activation-hash', $restored->getActivationHash());
    }

    public function testSerializationRoundTripPreservesLanguage(): void
    {
        $this->Account->setLanguage('de');

        $restored = unserialize(serialize($this->Account));

        $this->assertSame('de', $restored->getLanguage());
    }

    public function testSerializationRoundTripPreservesAdminRole(): void
    {
        $this->Account->setRole(UserRole::ROLE_ADMIN);

        $restored = unserialize(serialize($this->Account));

        $this->assertSame([UserRole::getRoleName(UserRole::ROLE_ADMIN)], $restored->getRoles());
    }

    public function testSerializationRoundTripPreservesAllowSupportFlag(): void
    {
        $this->Account->setAllowSupport(true);

        $restored = unserialize(serialize($this->Account));

        $this->assertTrue($restored->getAllowSupport());
    }

    public function testSerializationRoundTripPreservesIdAfterBeingSet(): void
    {
        $reflection = new \ReflectionProperty(Account::class, 'id');
        $reflection->setValue($this->Account, 42);

        $restored = unserialize(serialize($this->Account));

        $this->assertSame(42, $restored->getId());
    }
}
