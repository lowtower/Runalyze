<?php

declare(strict_types=1);

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
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
                PasswordAuthenticatedUserInterface::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                ],
            ],
        ]);
    }
};
