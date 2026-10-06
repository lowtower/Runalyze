<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Language;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class UserLocaleListener
{
    private const string SESSION_KEY = '_locale';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * @throws SessionNotFoundException if the user has a language but the request has no session
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->applyLocaleFor($event->getAuthenticationToken()->getUser());
    }

    /**
     * @throws SessionNotFoundException if the target user has a language but the request has no session
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $this->applyLocaleFor($event->getTargetUser());
    }

    /**
     * @throws SessionNotFoundException if the user has a language but the request has no session
     */
    private function applyLocaleFor(mixed $user): void
    {
        if (! $user instanceof Account || $user->getLanguage() === '') {
            return;
        }

        $this->requestStack->getSession()
            ->set(self::SESSION_KEY, $user->getLanguage());
        new Language($user->getLanguage());
    }
}
