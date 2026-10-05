<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Language;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($locale = $request->attributes->get('_locale')) {
            new Language($locale);
        } elseif ($request->hasPreviousSession() && $locale = $request->getSession()->get('_locale')) {
            new Language($locale);
        } else {
            new Language();
        }

        $locale = Language::getCurrentLanguage();

        $request->getSession()
            ->set('_locale', $locale);
        $request->setLocale($locale);
        $request->attributes->set('_locale', $locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}
