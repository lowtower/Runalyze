<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('monolog', [
        'channels' => [
            'deprecation',
            'queue',
            'console',
            'external_services',
            'activity_uploads',
        ],
        'handlers' => [
            'applog' => [
                'type' => 'rotating_file',
                'path' => '%kernel.project_dir%/var/log/symfony.log',
                'level' => 'error',
                'max_files' => 7,
            ],
            'main' => [
                'type' => 'fingers_crossed',
                'action_level' => 'warning',
                'handler' => 'file',
            ],
            'file' => [
                'type' => 'stream',
                'level' => 'debug',
            ],
            'syslog' => [
                'type' => 'syslog',
                'level' => 'error',
            ],
            'queue_file' => [
                'type' => 'rotating_file',
                'level' => 'debug',
                'path' => '%kernel.project_dir%/var/log/queue.log',
                'max_files' => 7,
                'channels' => [
                    'queue',
                ],
            ],
            'console_file' => [
                'type' => 'rotating_file',
                'level' => 'error',
                'path' => '%kernel.project_dir%/var/log/console.log',
                'max_files' => 7,
                'channels' => [
                    'console',
                ],
            ],
            'external_services_file' => [
                'type' => 'rotating_file',
                'level' => 'warning',
                'path' => '%kernel.project_dir%/var/log/external_services.log',
                'max_files' => 7,
                'channels' => [
                    'external_services',
                ],
            ],
            'activity_uploads_file' => [
                'type' => 'rotating_file',
                'level' => 'debug',
                'path' => '%kernel.project_dir%/var/log/activity_uploads.log',
                'max_files' => 7,
                'channels' => [
                    'activity_uploads',
                ],
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'prod') {
        $containerConfigurator->extension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'fingers_crossed',
                    'action_level' => 'error',
                    'handler' => 'nested',
                    'excluded_http_codes' => [
                        404,
                        405,
                    ],
                    'channels' => [
                        '!deprecation',
                    ],
                    'buffer_size' => 50,
                ],
                'nested' => [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'level' => 'debug',
                    'formatter' => 'monolog.formatter.json',
                ],
                'console' => [
                    'type' => 'console',
                    'process_psr_3_messages' => false,
                    'channels' => [
                        '!event',
                        '!doctrine',
                    ],
                ],
                'deprecation' => [
                    'type' => 'stream',
                    'channels' => [
                        'deprecation',
                    ],
                    'path' => 'php://stderr',
                    'formatter' => 'monolog.formatter.json',
                ],
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'dev') {
        $containerConfigurator->extension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'level' => 'debug',
                    'channels' => [
                        '!event',
                    ],
                ],
                'console' => [
                    'type' => 'console',
                    'process_psr_3_messages' => false,
                    'channels' => [
                        '!event',
                        '!doctrine',
                        '!console',
                    ],
                ],
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'fingers_crossed',
                    'action_level' => 'error',
                    'handler' => 'nested',
                    'excluded_http_codes' => [
                        404,
                        405,
                    ],
                    'channels' => [
                        '!event',
                    ],
                ],
                'nested' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'level' => 'debug',
                ],
            ],
        ]);
    }
};
