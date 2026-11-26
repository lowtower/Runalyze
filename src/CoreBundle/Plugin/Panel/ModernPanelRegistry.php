<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Panel;

use InvalidArgumentException;
use LogicException;

final readonly class ModernPanelRegistry
{
    /**
     * @var array<string, ModernPanelInterface>
     */
    private array $panels;

    /**
     * @param iterable<ModernPanelInterface> $panels
     */
    public function __construct(iterable $panels)
    {
        $registeredPanels = [];

        foreach ($panels as $panel) {
            $key = $panel->getKey();

            if (isset($registeredPanels[$key])) {
                throw new LogicException(sprintf('A modern panel with key "%s" is already registered.', $key));
            }

            $registeredPanels[$key] = $panel;
        }

        $this->panels = $registeredPanels;
    }

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        $keys = array_keys($this->panels);
        sort($keys, SORT_STRING);

        return $keys;
    }

    public function has(string $key): bool
    {
        return isset($this->panels[$key]);
    }

    public function find(string $key): ?ModernPanelInterface
    {
        return $this->panels[$key] ?? null;
    }

    /**
     * @throws InvalidArgumentException if no modern panel is registered for the given key
     */
    public function get(string $key): ModernPanelInterface
    {
        return $this->find($key) ?? throw new InvalidArgumentException(
            sprintf('No modern panel is registered for key "%s".', $key)
        );
    }
}
