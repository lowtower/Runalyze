<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserTimezoneListener
{
    public function __construct(
        private readonly UsageTrackingTokenStorage $token
    ) {
    }

    public function onKernelRequest(): void
    {
        if ($this->token->getToken() instanceof TokenInterface) {
            $account = $this->token->getToken()
                ->getUser();
            $this->token->getToken()
                ->getUser();

            if ($account instanceof Account) {
                $timezone = $account->getTimezone();

                if (Timezone::isValidValue($timezone)) {
                    $timezoneName = Timezone::getFullNameByEnum($timezone);

                    try {
                        new \DateTimeZone($timezoneName);
                    } catch (\Exception) {
                        return;
                    }

                    date_default_timezone_set($timezoneName);
                }
            }
        }
    }
}
