<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof Account && ! $user->isEnabled()) {
            throw new DisabledException('Your account has not been activated yet.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // keine Post-Auth-Prüfung nötig
    }
}
