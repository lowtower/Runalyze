<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;

final readonly class DoctrinePluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    private PluginConfRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        /** @var PluginConfRepository $repository */
        $repository = $entityManager->getRepository(PluginConf::class);
        $this->repository = $repository;
    }

    public function loadForPlugins(array $pluginIds): array
    {
        return $this->repository->findValuesForPlugins($pluginIds);
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        $plugin = $this->entityManager->getReference(Plugin::class, $pluginId);
        assert($plugin instanceof Plugin);

        $configuration = new PluginConf()
            ->setPlugin($plugin)
            ->setConfig($key)
            ->setValue($value);

        $this->repository->save($configuration);
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        $configuration = $this->repository->findForPluginAndKey($pluginId, $key);

        if ($configuration instanceof PluginConf) {
            $configuration->setValue($value);
            $this->repository->save($configuration);
        }
    }

    public function saveValue(Plugin $installation, string $key, string $value): void
    {
        $this->saveValues($installation, [
            $key => $value,
        ]);
    }

    /**
     * @param array<string, string> $values
     */
    public function saveValues(Plugin $installation, array $values): void
    {
        if ($values === []) {
            return;
        }

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($installation, $values): void {
            // A setting may not exist yet. Lock the installation before looking up its settings.
            $entityManager->lock($installation, LockMode::PESSIMISTIC_WRITE);
            $settings = $this->repository->findBy([
                'plugin' => $installation,
                'config' => array_keys($values),
            ]);

            $existing = [];
            foreach ($settings as $setting) {
                $key = $setting->getConfig();
                $setting->setValue($values[$key]);
                $existing[$key] = true;
            }

            foreach (array_diff_key($values, $existing) as $key => $value) {
                $entityManager->persist(new PluginConf()
                    ->setPlugin($installation)
                    ->setConfig($key)
                    ->setValue($value));
            }
        });
    }
}
