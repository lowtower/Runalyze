<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class SecurityConfigurationTest extends TestCase
{
    private const string ACCOUNT_CHECKER = AccountChecker::class;

    /**
     * @var array<string, mixed>
     */
    private array $security;

    protected function setUp(): void
    {
        $this->security = $this->loadSecurityConfiguration();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSecurityConfiguration(): array
    {
        return [
            'password_hashers' => [
                Account::class => [
                    'id' => 'app.sha256salted_encoder',
                ],
            ],
            'providers' => [
                'runalyzeuser' => [
                    'entity' => [
                        'class' => Account::class,
                    ],
                ],
            ],
            'role_hierarchy' => [
                'ROLE_ADMIN' => 'ROLE_USER',
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'setup' => [
                    'pattern' => '^/(update|install)',
                    'security' => false,
                ],
                'externalapi' => [
                    'pattern' => '^/api',
                    'http_basic' => true,
                    'provider' => 'runalyzeuser',
                    'stateless' => true,
                    'user_checker' => AccountChecker::class,
                ],
                'default' => [
                    'entry_point' => 'form_login',
                    'http_basic' => null,
                    'provider' => 'runalyzeuser',
                    'switch_user' => [
                        'role' => 'ROLE_ADMIN',
                        'parameter' => '_switch_user',
                    ],
                    'user_checker' => AccountChecker::class,
                    'logout' => [
                        'path' => '/logout',
                        'target' => '/',
                    ],
                    'stateless' => false,
                    'form_login' => [
                        'login_path' => 'login',
                        'check_path' => 'login_check',
                        'enable_csrf' => true,
                        'use_referer' => true,
                    ],
                    'remember_me' => [
                        'secret' => '%app.secret%',
                        'lifetime' => 604800,
                        'path' => '/',
                    ],
                ],
            ],
            'access_control' => [
                [
                    'path' => '^/api',
                    'roles' => 'ROLE_USER',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function firewalls(): array
    {
        return $this->security['firewalls'];
    }

    private function resolveFirewall(string $path): string
    {
        foreach ($this->firewalls() as $name => $firewall) {
            if (! isset($firewall['pattern']) || preg_match('{' . $firewall['pattern'] . '}', $path) === 1) {
                return $name;
            }
        }

        $this->fail('No firewall matches ' . $path);
    }

    public function testOnlyTheLastFirewallHasNoPatternSoThatItDoesNotShadowTheOthers(): void
    {
        $firewallsWithoutPattern = array_keys(array_filter(
            $this->firewalls(),
            static fn (array $firewall): bool => ! isset($firewall['pattern'])
        ));

        $this->assertSame([array_key_last($this->firewalls())], $firewallsWithoutPattern);
    }

    #[DataProvider('pathsAndTheirFirewalls')]
    public function testRequestsAreHandledByTheExpectedFirewall(string $path, string $expectedFirewall): void
    {
        $this->assertSame($expectedFirewall, $this->resolveFirewall($path));
    }

    public static function pathsAndTheirFirewalls(): iterable
    {
        yield 'activity import api' => ['/api/import/activity', 'externalapi'];
        yield 'api root' => ['/api/', 'externalapi'];
        yield 'installer' => ['/install', 'setup'];
        yield 'installer step' => ['/install/finish', 'setup'];
        yield 'updater' => ['/update', 'setup'];
        yield 'profiler' => ['/_profiler/abc123', 'dev'];
        yield 'web debug toolbar' => ['/_wdt/abc123', 'dev'];
        yield 'stylesheet' => ['/css/style.css', 'dev'];
        yield 'tools page' => ['/my/tools', 'default'];
        yield 'settings page' => ['/settings/account', 'default'];
        yield 'login page' => ['/login', 'default'];
        yield 'path that only contains api' => ['/my/api', 'default'];
    }

    public function testApiFirewallIsStatelessAndUsesHttpBasicAuthentication(): void
    {
        $api = $this->firewalls()['externalapi'];

        $this->assertTrue($api['stateless']);
        $this->assertTrue($api['http_basic']);
    }

    public function testFirewallForUsersIsNotStateless(): void
    {
        $this->assertFalse($this->firewalls()['default']['stateless']);
    }

    public function testFirewallsWithoutSecurityDoNotRequireAProvider(): void
    {
        foreach (['dev', 'setup'] as $name) {
            $this->assertFalse($this->firewalls()[$name]['security'], $name);
        }
    }

    public function testApiFirewallAndUserFirewallRejectAccountsThatAreNotActivated(): void
    {
        $this->assertSame(self::ACCOUNT_CHECKER, $this->firewalls()['externalapi']['user_checker']);
        $this->assertSame(self::ACCOUNT_CHECKER, $this->firewalls()['default']['user_checker']);
    }

    public function testEveryFirewallWithAuthenticationUsesAnExistingProvider(): void
    {
        $providers = array_keys($this->security['providers']);

        foreach ($this->firewalls() as $name => $firewall) {
            if (($firewall['security'] ?? true) === false) {
                continue;
            }

            $this->assertContains($firewall['provider'], $providers, $name);
        }
    }

    public function testAccessControlRequiresLoggedInUserForTheApi(): void
    {
        $this->assertContains(
            [
                'path' => '^/api',
                'roles' => 'ROLE_USER',
            ],
            $this->security['access_control']
        );
    }

    public function testAdminsInheritTheRightsOfUsers(): void
    {
        $this->assertSame('ROLE_USER', $this->security['role_hierarchy']['ROLE_ADMIN']);
    }

    public function testPasswordsOfAccountsAreHashedByTheLegacyEncoder(): void
    {
        $this->assertSame(
            'app.sha256salted_encoder',
            $this->security['password_hashers'][Account::class]['id']
        );
    }

    public function testPasswordHashingIsMadeCheapInTheTestEnvironmentOnly(): void
    {
        $this->assertArrayNotHasKey(
            PasswordAuthenticatedUserInterface::class,
            $this->security['password_hashers']
        );
    }
}
