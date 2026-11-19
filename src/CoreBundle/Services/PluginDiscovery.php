<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use FilesystemIterator;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use RuntimeException;

final readonly class PluginDiscovery
{
    public function __construct(
        private string $pluginDirectory,
        private ?ModernPanelRegistry $modernPanelRegistry = null
    ) {}

    /**
     * @return list<string>
     *
     * @throws RuntimeException if the plugin directory does not exist or cannot be read
     */
    public function findAvailablePluginKeys(): array
    {
        if (! is_dir($this->pluginDirectory) || ! is_readable($this->pluginDirectory)) {
            throw new RuntimeException(sprintf(
                'Plugin directory "%s" does not exist or is not readable.',
                $this->pluginDirectory
            ));
        }

        $keys = [];

        foreach (new FilesystemIterator($this->pluginDirectory, FilesystemIterator::SKIP_DOTS) as $entry) {
            if (! str_starts_with($entry->getFilename(), '.') && $entry->isDir()) {
                $keys[] = $entry->getFilename();
            }
        }

        if ($this->modernPanelRegistry instanceof \Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry) {
            $keys = array_values(array_unique([...$keys, ...$this->modernPanelRegistry->getKeys()]));
        }

        sort($keys, SORT_STRING);

        return $keys;
    }
}
