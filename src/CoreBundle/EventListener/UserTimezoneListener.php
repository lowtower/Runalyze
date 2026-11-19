<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UserTimezoneListener
{
    public function __construct(
        private readonly TokenStorageInterface $token
    ) {}

    #[AsEventListener(event: 'kernel.request')]
    public function onKernelRequest(): void
    {
        $user = $this->token->getToken()?->getUser();

        if (! $user instanceof Account) {
            return;
        }

        $timezone = $user->getTimezone();

        if (! Timezone::isValidValue($timezone)) {
            return;
        }

        $timezoneName = Timezone::getFullNameByEnum($timezone);

        try {
            new \DateTimeZone($timezoneName);
        } catch (\Exception) {
            return;
        }

        date_default_timezone_set($timezoneName);
    }
}
