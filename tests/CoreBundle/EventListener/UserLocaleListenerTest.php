<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\EventListener\UserLocaleListener;
use Runalyze\Language;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

#[\PHPUnit\Framework\Attributes\Group('default')]
class UserLocaleListenerTest extends TestCase
{
    private string $previousLanguage;

    private Session $session;

    private Request $request;

    private UserLocaleListener $listener;

    protected function setUp(): void
    {
        $this->previousLanguage = Language::getCurrentLanguage();

        $this->session = new Session(new MockArraySessionStorage());
        $this->request = new Request();
        $this->request->setSession($this->session);

        $requestStack = new RequestStack([$this->request]);

        $this->listener = new UserLocaleListener($requestStack);
    }

    protected function tearDown(): void
    {
        new Language($this->previousLanguage);
    }

    private function createAccount(string $language): Account
    {
        return new Account()->setLanguage($language);
    }

    private function login(UserInterface $user): void
    {
        $token = new UsernamePasswordToken($user, 'default', ['ROLE_USER']);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $token));
    }

    public function testLoginStoresLanguageOfAccountInSessionAndAppliesIt(): void
    {
        new Language('en');

        $this->login($this->createAccount('de'));

        $this->assertSame('de', $this->session->get('_locale'));
        $this->assertSame('de', Language::getCurrentLanguage());
    }

    public function testSwitchingUserStoresLanguageOfTargetAccountInSessionAndAppliesIt(): void
    {
        new Language('en');

        $this->listener->onSwitchUser(new SwitchUserEvent($this->request, $this->createAccount('de')));

        $this->assertSame('de', $this->session->get('_locale'));
        $this->assertSame('de', Language::getCurrentLanguage());
    }

    public function testLoginOfAccountWithoutLanguageLeavesSessionUntouched(): void
    {
        $this->login($this->createAccount(''));

        $this->assertFalse($this->session->isStarted());
    }

    public function testLoginOfUserThatIsNoAccountLeavesSessionUntouched(): void
    {
        $this->login(new InMemoryUser('memory-user', 'password'));

        $this->assertFalse($this->session->isStarted());
    }

    public function testSwitchingToUserThatIsNoAccountLeavesSessionUntouched(): void
    {
        $this->listener->onSwitchUser(new SwitchUserEvent($this->request, new InMemoryUser('memory-user', 'password')));

        $this->assertFalse($this->session->isStarted());
    }

    public function testLoginWithoutSessionThrowsSessionNotFoundException(): void
    {
        $listener = new UserLocaleListener(new RequestStack());
        $token = new UsernamePasswordToken($this->createAccount('de'), 'default', ['ROLE_USER']);

        $this->expectException(SessionNotFoundException::class);

        $listener->onInteractiveLogin(new InteractiveLoginEvent(new Request(), $token));
    }
}
