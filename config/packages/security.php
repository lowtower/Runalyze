<?php

declare(strict_types=1);

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            // legacy hasher (sha256 + per-account salt): only used to verify
            // old hashes during login until each account has been migrated
            'legacy_sha256salted' => [
                'id' => Sha256SaltedEncoder::class,
            ],
            Account::class => [
                'algorithm' => 'auto',
                'migrate_from' => [
                    'legacy_sha256salted',
                ],
            ],
        ],
        'providers' => [
            'runalyzeuser' => [
                'entity' => [
                    'class' => Account::class,
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_profiler(/|$)|(_wdt|assets|build|css|images|js)/)',
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
                'login_throttling' => [
                    'max_attempts' => 5,
                ],
                'remember_me' => [
                    'secret' => '%app.secret%',
                    'lifetime' => 604800,
                    'path' => '/',
                ],
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => 'ROLE_USER',
        ],
        'access_control' => [
            [
                'path' => '^/api',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('security', [
            'password_hashers' => [
                Account::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                    'migrate_from' => [
                        'legacy_sha256salted',
                    ],
                ],
            ],
        ]);
    }
};
