<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Language;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Stores the locale of the user in the session after the
 * login. This can be used by the LocaleListener afterwards.
 */
class UserLocaleListener
{
    public function __construct(
        private readonly Session $session
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()
            ->getUser();

        if ($user instanceof Account && $user->getLanguage() != null) {
            $this->session->set('_locale', $user->getLanguage());
            new Language($user->getLanguage());
        }
    }
}
