<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SecurityConfigurationTest extends TestCase
{
    private const string ACCOUNT_CHECKER = \Runalyze\Bundle\CoreBundle\Services\AccountChecker::class;

    /**
     * @var array<string, mixed>
     */
    private array $security;

    protected function setUp(): void
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 3) . '/config/packages/security.yaml');

        $this->security = $configuration['security'];
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
            $this->security['password_hashers'][\Runalyze\Bundle\CoreBundle\Entity\Account::class]['id']
        );
    }

    public function testPasswordHashingIsMadeCheapInTheTestEnvironmentOnly(): void
    {
        $this->assertArrayNotHasKey(
            \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class,
            $this->security['password_hashers']
        );
    }
}
