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
                'async' => '%env(MESSENGER_TRANSPORT_DSN)%',
                'failed' => 'doctrine://default?queue_name=failed&auto_setup=0',
            ],
            'routing' => [
                BackupGenerationMessage::class => 'async',
                PosterGenerationMessage::class => 'async',
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('framework', [
            'messenger' => [
                'transports' => [
                    'async' => 'in-memory://',
                    'failed' => 'in-memory://',
                ],
            ],
        ]);
    }
};
