<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use FilesystemIterator;
use RuntimeException;

final readonly class PluginDiscovery
{
    public function __construct(
        private string $pluginDirectory
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

        sort($keys, SORT_STRING);

        return $keys;
    }
}
