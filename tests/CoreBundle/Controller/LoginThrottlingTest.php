<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class LoginThrottlingTest extends AbstractFixturesAwareWebTestCase
{
    /**
     * Failed attempts before the rate limiter kicks in; must match
     * 'login_throttling' => ['max_attempts' => 5] in config/packages/security.php.
     */
    private const int MAX_ATTEMPTS = 5;

    private const string WRONG_PASSWORD = 'throttling-test-wrong-password';

    /**
     * The rate limiter state is persisted in the cache, so it survives single
     * requests and even test runs within the limiter interval. Every test run
     * therefore needs its own username to start with an untouched bucket for
     * the local (username + IP) limiter.
     */
    private function uniqueUsername(): string
    {
        return 'throttling_' . uniqid('', true);
    }

    private function submitLoginForm(KernelBrowser $client, string $username): void
    {
        $crawler = $client->request('GET', '/en/login');

        $form = $crawler->filter('form')
            ->form([
                '_username' => $username,
                '_password' => self::WRONG_PASSWORD,
            ]);

        $client->submit($form);
    }

    private function lastAuthenticationError(KernelBrowser $client): ?AuthenticationException
    {
        $session = $client->getRequest()?->getSession();

        $error = $session?->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        return $error instanceof AuthenticationException ? $error : null;
    }

    public function testSixthFailedLoginAttemptForSameUsernameIsThrottled(): void
    {
        $username = $this->uniqueUsername();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            $client = $this->makeClient();
            $this->submitLoginForm($client, $username);

            $this->assertStatusCode(302, $client);
            $this->assertNotInstanceOf(
                TooManyLoginAttemptsAuthenticationException::class,
                $this->lastAuthenticationError($client),
                sprintf('Failed attempt %d must not be throttled yet.', $attempt)
            );
        }

        $client = $this->makeClient();
        $this->submitLoginForm($client, $username);

        $this->assertStatusCode(302, $client);
        $this->assertInstanceOf(
            TooManyLoginAttemptsAuthenticationException::class,
            $this->lastAuthenticationError($client),
            'The sixth failed login attempt for the same username must be throttled.'
        );
    }

    public function testFailedLoginAttemptsDoNotThrottleOtherUsernames(): void
    {
        $exhaustedUsername = $this->uniqueUsername();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            $client = $this->makeClient();
            $this->submitLoginForm($client, $exhaustedUsername);

            $this->assertStatusCode(302, $client);
        }

        $client = $this->makeClient();
        $this->submitLoginForm($client, $this->uniqueUsername());

        $this->assertStatusCode(302, $client);
        $this->assertNotInstanceOf(
            TooManyLoginAttemptsAuthenticationException::class,
            $this->lastAuthenticationError($client),
            'Other usernames must have their own rate limiter bucket.'
        );
    }
}
