<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicTranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('translator.default')) {
            $container->getDefinition('translator.default')
                ->setPublic(true);
        }
    }
}
