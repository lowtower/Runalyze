<?php

declare(strict_types=1);

use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'messenger' => [
            'failure_transport' => 'failed',
            'transports' => [
                'backup' => '%env(MESSENGER_BACKUP_TRANSPORT_DSN)%',
                'poster' => '%env(MESSENGER_POSTER_TRANSPORT_DSN)%',
                'garmin' => '%env(MESSENGER_GARMIN_TRANSPORT_DSN)%',
                'failed' => 'doctrine://default?queue_name=failed&auto_setup=0',
            ],
            'routing' => [
                BackupGenerationMessage::class => 'backup',
                PosterGenerationMessage::class => 'poster',
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('framework', [
            'messenger' => [
                'transports' => [
                    'backup' => 'in-memory://',
                    'poster' => 'in-memory://',
                    'garmin' => 'in-memory://',
                    'failed' => 'in-memory://',
                ],
            ],
        ]);
    }
};
