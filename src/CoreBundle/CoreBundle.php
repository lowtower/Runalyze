<?php

namespace Runalyze\Bundle\CoreBundle;

use Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundles are auto configured as Twig paths, public resources etc.
 * So having this will bootstrap a lot of boilerplate for console commands
 * and so on.
 */
class CoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
