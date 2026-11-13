<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use RuntimeException;

#[Group('plugin')]
final class PluginDiscoveryTest extends TestCase
{
    private string $pluginDirectory;

    protected function setUp(): void
    {
        $this->pluginDirectory = sys_get_temp_dir() . '/runalyze-plugin-discovery-' . bin2hex(random_bytes(8));
        mkdir($this->pluginDirectory);
    }

    protected function tearDown(): void
    {
        foreach (new FilesystemIterator($this->pluginDirectory, FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($this->pluginDirectory);
    }

    public function testFindsPluginDirectoriesAndIgnoresFilesAndHiddenEntries(): void
    {
        mkdir($this->pluginDirectory . '/RunalyzePluginStat_Weather');
        mkdir($this->pluginDirectory . '/RunalyzePluginPanel_Sports');
        mkdir($this->pluginDirectory . '/.git');
        file_put_contents($this->pluginDirectory . '/README.md', 'Not a plugin directory');

        $discovery = new PluginDiscovery($this->pluginDirectory);

        $this->assertSame([
            'RunalyzePluginPanel_Sports',
            'RunalyzePluginStat_Weather',
        ], $discovery->findAvailablePluginKeys());
    }

    public function testFindsNewPluginDirectoriesWithoutRecreatingTheService(): void
    {
        $discovery = new PluginDiscovery($this->pluginDirectory);

        mkdir($this->pluginDirectory . '/FirstPlugin');
        $this->assertSame(['FirstPlugin'], $discovery->findAvailablePluginKeys());

        mkdir($this->pluginDirectory . '/SecondPlugin');
        $this->assertSame(['FirstPlugin', 'SecondPlugin'], $discovery->findAvailablePluginKeys());
    }

    public function testThrowsWhenPluginDirectoryDoesNotExist(): void
    {
        $discovery = new PluginDiscovery($this->pluginDirectory . '/missing');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('does not exist or is not readable');

        $discovery->findAvailablePluginKeys();
    }
}
