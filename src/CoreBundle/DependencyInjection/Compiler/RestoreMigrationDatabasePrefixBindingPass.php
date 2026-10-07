<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\DependencyInjection\Compiler;

use Doctrine\Migrations\AbstractMigration;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RestoreMigrationDatabasePrefixBindingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if ($class === null || ! is_a($class, AbstractMigration::class, true)) {
                continue;
            }

            if (! $this->constructorDeclaresDatabasePrefix($class)) {
                continue;
            }

            $definition->setBindings($definition->getBindings() + [
                '$DatabasePrefix' => new BoundArgument('%app.database_prefix%', false),
            ]);
        }
    }

    private function constructorDeclaresDatabasePrefix(string $class): bool
    {
        $constructor = new ReflectionClass($class)->getConstructor();

        if ($constructor === null) {
            return false;
        }

        return array_any($constructor->getParameters(), fn (\ReflectionParameter $parameter): bool => $parameter->getName() === 'DatabasePrefix');
    }
}
