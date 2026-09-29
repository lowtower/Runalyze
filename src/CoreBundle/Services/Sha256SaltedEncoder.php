<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class Sha256SaltedEncoder implements LegacyPasswordHasherInterface
{
    /**
     * @throws InvalidPasswordException if $user is not an Account (i.e. does not have a salt
     *                                    column this hasher can use)
     */
    public function getLegacyEncoderPassword(PasswordAuthenticatedUserInterface $user): string
    {
        if (! $user instanceof Account) {
            throw new InvalidPasswordException('Sha256SaltedEncoder can only be used for Account users, which provide the salt this hasher requires.');
        }

        return $user->getSalt();
    }

    public function hash(string $plainPassword, ?string $salt = null): string
    {
        return hash('sha256', trim($plainPassword) . $salt);
    }

    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        return hash_equals($hashedPassword, $this->hash($plainPassword, $salt));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }
}
