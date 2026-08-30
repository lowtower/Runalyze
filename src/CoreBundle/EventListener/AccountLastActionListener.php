<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;

class AccountLastActionListener
{
    protected UsageTrackingTokenStorage $token;

    protected EntityManager $em;

    public function __construct(UsageTrackingTokenStorage $token, EntityManager $manager)
    {
        $this->token = $token;
        $this->em = $manager;
    }

    /**
     * Update the user "lastaction" on each request
     */
    public function onCoreController(\Symfony\Component\HttpKernel\Event\ControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        if ($this->token->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\TokenInterface) {
            $account = $this->token->getToken()
                ->getUser();
            $this->token->getToken()
                ->getUser();

            if ($account instanceof Account && $account->getLastAction() < strtotime('2 minutes ago')) {
                $account->setLastAction();
                $this->em->flush($account);
            }
        }
    }
}
