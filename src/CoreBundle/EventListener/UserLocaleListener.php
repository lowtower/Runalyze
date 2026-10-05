<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Language;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class UserLocaleListener
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->applyLocaleFor($event->getAuthenticationToken()->getUser());
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $this->applyLocaleFor($event->getTargetUser());
    }

    private function applyLocaleFor(mixed $user): void
    {
        if ($user instanceof Account && $user->getLanguage() != null) {
            $this->requestStack->getSession()
                ->set('_locale', $user->getLanguage());
            new Language($user->getLanguage());
        }
    }
}
