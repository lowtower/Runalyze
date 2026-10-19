<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\EventListener\LocaleListener;
use Runalyze\Language;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class LocaleListenerTest extends TestCase
{
    private string $previousLanguage;

    private LocaleListener $listener;

    protected function setUp(): void
    {
        $this->previousLanguage = Language::getCurrentLanguage();
        $this->listener = new LocaleListener();
    }

    protected function tearDown(): void
    {
        new Language($this->previousLanguage);
    }

    private function handle(Request $request): void
    {
        $this->listener->onKernelRequest(
            new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST)
        );
    }

    private function createSession(?string $storedLocale = null): Session
    {
        $session = new Session(new MockArraySessionStorage());

        if ($storedLocale !== null) {
            $session->set('_locale', $storedLocale);
        }

        return $session;
    }

    private function createRequest(?Session $session, bool $hasPreviousSession): Request
    {
        $request = new Request();

        if ($session instanceof Session) {
            $request->setSession($session);

            if ($hasPreviousSession) {
                $request->cookies->set($session->getName(), 'id-of-an-existing-session');
            }
        }

        return $request;
    }

    public function testRequestWithoutSessionGetsLocaleAndDoesNotFail(): void
    {
        $request = $this->createRequest(null, false);

        $this->handle($request);

        $this->assertSame(Language::getCurrentLanguage(), $request->getLocale());
        $this->assertSame(Language::getCurrentLanguage(), $request->attributes->get('_locale'));
    }

    public function testRequestWithoutPreviousSessionDoesNotStartTheSession(): void
    {
        $session = $this->createSession();
        $request = $this->createRequest($session, false);

        $this->handle($request);

        $this->assertFalse($session->isStarted());
    }

    public function testRequestWithoutPreviousSessionStillGetsLocaleOnRequestAndAttributes(): void
    {
        $request = $this->createRequest($this->createSession(), false);

        $this->handle($request);

        $this->assertSame(Language::getCurrentLanguage(), $request->getLocale());
        $this->assertSame(Language::getCurrentLanguage(), $request->attributes->get('_locale'));
    }

    public function testSessionLocaleWithoutSessionCookieIsIgnored(): void
    {
        new Language('en');
        $session = $this->createSession('de');
        $request = $this->createRequest($session, false);

        $this->handle($request);

        $this->assertSame('de', $session->get('_locale'), 'a session that was not sent by the client must stay untouched');
    }

    #[DataProvider('storedAndOtherLocales')]
    public function testPreviousSessionLocaleIsUsed(string $storedLocale, string $otherLocale): void
    {
        new Language($otherLocale);
        $session = $this->createSession($storedLocale);
        $request = $this->createRequest($session, true);

        $this->handle($request);

        $this->assertSame($storedLocale, Language::getCurrentLanguage());
        $this->assertSame($storedLocale, $request->getLocale());
        $this->assertSame($storedLocale, $request->attributes->get('_locale'));
        $this->assertSame($storedLocale, $session->get('_locale'));
    }

    public static function storedAndOtherLocales(): iterable
    {
        yield 'german stored while english is current' => ['de', 'en'];
        yield 'english stored while german is current' => ['en', 'de'];
    }

    public function testPreviousSessionWithoutStoredLocaleReceivesTheDetectedLocale(): void
    {
        $session = $this->createSession();
        $session->set('something-else', 'value');

        $request = $this->createRequest($session, true);

        $this->handle($request);

        $this->assertSame(Language::getCurrentLanguage(), $session->get('_locale'));
        $this->assertSame('value', $session->get('something-else'));
    }

    #[DataProvider('routeLocales')]
    public function testLocaleFromRouteIsUsedAndRememberedEvenWithoutPreviousSession(string $routeLocale): void
    {
        $session = $this->createSession();
        $request = $this->createRequest($session, false);
        $request->attributes->set('_locale', $routeLocale);

        $this->handle($request);

        $this->assertSame($routeLocale, Language::getCurrentLanguage());
        $this->assertSame($routeLocale, $request->getLocale());
        $this->assertSame($routeLocale, $session->get('_locale'));
    }

    public static function routeLocales(): iterable
    {
        yield 'german' => ['de'];
        yield 'english' => ['en'];
    }

    public function testLocaleFromRouteWinsOverLocaleOfPreviousSession(): void
    {
        $session = $this->createSession('de');
        $request = $this->createRequest($session, true);
        $request->attributes->set('_locale', 'en');

        $this->handle($request);

        $this->assertSame('en', Language::getCurrentLanguage());
        $this->assertSame('en', $session->get('_locale'));
    }

    public function testRouteLocaleOnRequestWithoutSessionDoesNotFail(): void
    {
        $request = $this->createRequest(null, false);
        $request->attributes->set('_locale', 'de');

        $this->handle($request);

        $this->assertSame('de', $request->getLocale());
    }

    public function testListenerRunsAfterTheDefaultLocaleListenerOfSymfony(): void
    {
        $this->assertSame(
            [
                KernelEvents::REQUEST => [['onKernelRequest', 15]],
            ],
            LocaleListener::getSubscribedEvents()
        );
    }
}
