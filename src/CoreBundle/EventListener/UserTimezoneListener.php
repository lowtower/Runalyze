<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;

class UserTimezoneListener
{
    private UsageTrackingTokenStorage $token;

    public function __construct(UsageTrackingTokenStorage $token)
    {
        $this->token = $token;
    }

    public function onKernelRequest()
    {
        if ($this->token->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\TokenInterface) {
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
                    } catch (\Exception $e) {
                        return;
                    }

                    date_default_timezone_set($timezoneName);
                }
            }
        }
    }
}
