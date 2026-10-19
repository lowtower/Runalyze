<?php

namespace App;

use Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler\PublicTranslatorPass;
use Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler\RestoreMigrationDatabasePrefixBindingPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test', 'test_empty'];
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
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

        // load php config
        $loader->load($confDir . '/{packages}/*.php', 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*.php', 'glob');

        // load services
        if (file_exists($confDir . '/services.php')) {
            $loader->load($confDir . '/services.php');
        }

        if (file_exists($confDir . '/services_' . $this->environment . '.php')) {
            $loader->load($confDir . '/services_' . $this->environment . '.php');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/*.php');
        $routes->import($confDir . '/{routes}/*.php');

        if (is_file($confDir . '/routes.php')) {
            $routes->import($confDir . '/routes.php');
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PublicTranslatorPass());
        $container->addCompilerPass(
            new RestoreMigrationDatabasePrefixBindingPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -10
        );
    }
}
