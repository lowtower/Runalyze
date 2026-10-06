<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Runalyze\Language;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    private const string SESSION_KEY = '_locale';

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $routeLocale = $this->readRouteLocale($request);
        $sessionLocale = $this->readLocaleOfPreviousSession($request);

        if ($routeLocale !== null) {
            new Language($routeLocale);
        } elseif ($sessionLocale !== null) {
            new Language($sessionLocale);
        } else {
            new Language();
        }

        $locale = Language::getCurrentLanguage();

        if ($this->mayWriteToSession($request, $routeLocale)) {
            $request->getSession()
                ->set(self::SESSION_KEY, $locale);
        }

        $request->setLocale($locale);
        $request->attributes->set(self::SESSION_KEY, $locale);
    }

    private function mayWriteToSession(Request $request, ?string $routeLocale): bool
    {
        return $request->hasSession() && ($routeLocale !== null || $request->hasPreviousSession());
    }

    private function readRouteLocale(Request $request): ?string
    {
        $locale = $request->attributes->get(self::SESSION_KEY);

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    private function readLocaleOfPreviousSession(Request $request): ?string
    {
        if (! $request->hasPreviousSession()) {
            return null;
        }

        $locale = $request->getSession()
            ->get(self::SESSION_KEY);

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    /**
     * @return array<string, array<int, array{0: string, 1: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}
