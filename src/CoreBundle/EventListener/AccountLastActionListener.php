<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountLastActionListener
{
    public function __construct(
        protected UsageTrackingTokenStorage $token,
        protected EntityManager $em
    ) {
    }

    /**
     * Update the user "lastaction" on each request
     */
    public function onCoreController(ControllerEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernel::MAIN_REQUEST) {
            return;
        }

        if ($this->token->getToken() instanceof TokenInterface) {
            $account = $this->token->getToken()
                ->getUser();

            if ($account instanceof Account && $account->getLastAction() < strtotime('2 minutes ago')) {
                $account->setLastAction();
                $this->em->flush($account);
            }
        }
    }
}
