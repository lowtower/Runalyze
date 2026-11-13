<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class PluginConfRepositoryTest extends AbstractRepositoryTestCase
{
    private PluginConfRepository $repository;

    private Plugin $plugin;

    private int $pluginId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getEntityManager()->getRepository(PluginConf::class);

        $this->plugin = (new Plugin())
            ->setAccount($this->getEmptyAccount())
            ->setKey('PluginConfRepositoryTest')
            ->setType(Plugin::TYPE_STAT);

        $this->getEntityManager()->persist($this->plugin);
        $this->getEntityManager()->flush();
        $pluginId = $this->plugin->getId();
        self::assertNotNull($pluginId);
        $this->pluginId = $pluginId;
    }

    public function testFindValuesForPluginsGroupsValuesByPluginId(): void
    {
        $this->repository->save(
            (new PluginConf())
                ->setPlugin($this->plugin)
                ->setConfig('first')
                ->setValue('one')
        );
        $this->repository->save(
            (new PluginConf())
                ->setPlugin($this->plugin)
                ->setConfig('second')
                ->setValue('two')
        );

        $this->assertSame([
            $this->pluginId => [
                'first' => 'one',
                'second' => 'two',
            ],
        ], $this->repository->findValuesForPlugins([$this->pluginId]));
    }

    public function testFindValuesForPluginsReturnsEmptyArrayWithoutPluginIds(): void
    {
        $this->assertSame([], $this->repository->findValuesForPlugins([]));
    }


    public function testDoctrineStorageInsertsUpdatesAndLoadsValues(): void
    {
        $storage = new DoctrinePluginConfigurationStorage($this->getEntityManager());

        $storage->insert($this->pluginId, 'setting', 'initial');
        $storage->update($this->pluginId, 'setting', 'updated');

        $this->assertSame([
            $this->pluginId => [
                'setting' => 'updated',
            ],
        ], $storage->loadForPlugins([$this->pluginId]));
    }

    public function testFindForPluginAndKeyReturnsMatchingConfiguration(): void
    {
        $configuration = (new PluginConf())
            ->setPlugin($this->plugin)
            ->setConfig('setting')
            ->setValue('enabled');
        $this->repository->save($configuration);

        $this->assertSame(
            $configuration,
            $this->repository->findForPluginAndKey($this->pluginId, 'setting')
        );
    }
}
