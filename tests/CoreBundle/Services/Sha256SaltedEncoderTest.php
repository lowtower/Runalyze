<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
class Sha256SaltedEncoderTest extends TestCase
{
    private const string SHA256_OF_ABC = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad';

    private const string SHA256_OF_PASSWORD_AND_SALT = '7a37b85c8918eac19a9089c0fa5a2ab4dce3f90528dcdeec108b23ddf3607b99';

    private Sha256SaltedEncoder $Encoder;

    protected function setUp(): void
    {
        $this->Encoder = new Sha256SaltedEncoder();
    }

    public function testValidation(): void
    {
        $this->assertTrue(
            $this->Encoder->verify(
                $this->Encoder->hash('Th1$PassSwordIsSoDamnGreat!', 'Salt and pepper ...'),
                'Th1$PassSwordIsSoDamnGreat!',
                'Salt and pepper ...'
            )
        );
    }

    public function testValidationOfWrongPassword(): void
    {
        $this->assertFalse(
            $this->Encoder->verify(
                $this->Encoder->hash('Th1$PassSwordIsSoDamnGreat!', 'A password is no sword.'),
                'Th1$PasSwordIsSoDamnGreat!',
                'A password is no sword.'
            )
        );
    }

    public function testHashWithoutSaltIsPlainSha256OfThePassword(): void
    {
        $this->assertSame(self::SHA256_OF_ABC, $this->Encoder->hash('abc'));
    }

    public function testHashAppendsSaltToThePassword(): void
    {
        $this->assertSame(self::SHA256_OF_PASSWORD_AND_SALT, $this->Encoder->hash('password', 'salt'));
    }

    public function testHashIgnoresWhitespaceAroundThePassword(): void
    {
        $this->assertSame(
            $this->Encoder->hash('abc', 'salt'),
            $this->Encoder->hash("  abc \n", 'salt')
        );
    }

    public function testHashDoesNotIgnoreWhitespaceInsideThePassword(): void
    {
        $this->assertNotSame(
            $this->Encoder->hash('ab c', 'salt'),
            $this->Encoder->hash('abc', 'salt')
        );
    }

    public function testVerifyRejectsHashCreatedWithAnotherSalt(): void
    {
        $this->assertFalse(
            $this->Encoder->verify($this->Encoder->hash('abc', 'salt'), 'abc', 'other salt')
        );
    }

    #[DataProvider('nonMatchingHashes')]
    public function testVerifyRejectsHashesThatAreNotTheHashOfThePassword(string $hashedPassword): void
    {
        $this->assertFalse($this->Encoder->verify($hashedPassword, 'abc'));
    }

    public static function nonMatchingHashes(): iterable
    {
        yield 'empty hash' => [''];
        yield 'hash in upper case' => [strtoupper(self::SHA256_OF_ABC)];
        yield 'truncated hash' => [substr(self::SHA256_OF_ABC, 0, 32)];
    }

    public function testVerifyAcceptsPlainSha256WhenNoSaltIsGiven(): void
    {
        $this->assertTrue($this->Encoder->verify(self::SHA256_OF_ABC, 'abc'));
    }

    public function testLegacyEncoderPasswordOfAccountIsItsSalt(): void
    {
        $account = new Account();
        $account->setSalt('the salt of the account');

        $this->assertSame('the salt of the account', $this->Encoder->getLegacyEncoderPassword($account));
    }

    public function testLegacyEncoderPasswordThrowsInvalidPasswordExceptionForUsersThatAreNoAccount(): void
    {
        $userWithoutSalt = new class() implements PasswordAuthenticatedUserInterface {
            public function getPassword(): string
            {
                return 'hashed password';
            }
        };

        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Account');

        $this->Encoder->getLegacyEncoderPassword($userWithoutSalt);
    }
}
