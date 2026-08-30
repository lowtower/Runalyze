<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class Sha256SaltedEncoder implements PasswordEncoderInterface
{
    /**
     * @param string $raw
     * @param string $salt
     */
    public function encodePassword($raw, $salt): string
    {
        return hash('sha256', trim($raw) . $salt); // Custom function for password encrypt
    }

    /**
     * @param string $encoded
     * @param string $raw
     * @param string $salt
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        return $encoded === $this->encodePassword($raw, $salt);
    }

    /**
     * Checks if the given hash requires a rehash.
     *
     * @param string $encoded The encoded password
     *
     * @return bool true if the password needs to be rehashed, false otherwise
     */
    public function needsRehash(string $encoded): bool
    {
        return true;
    }
}
