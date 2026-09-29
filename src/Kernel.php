<?php

namespace App;

use Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler\PublicTranslatorPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../var/log';
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $loader->load($confDir . '/{packages}/*.yaml', 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*.yaml', 'glob');

        if (file_exists($confDir . '/services.yaml')) {
            $loader->load($confDir . '/services.yaml');
            $loader->load($confDir . '/{services}_' . $this->environment . '.yaml', 'glob');
        }
    }

    protected function configureRoutes(\Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/*.yaml', '/', 'glob');
        $routes->import($confDir . '/{routes}/*.yaml', '/', 'glob');

        if (file_exists($confDir . '/routes.yaml')) {
            $routes->import($confDir . '/routes.yaml', '/');
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PublicTranslatorPass());
    }
}
