<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component;

trait VariablesContainerTrait
{
    protected array $Variables = [];

    public function set(string $key, mixed $value): void
    {
        $this->Variables[$key] = $value;
    }

    public function get(string $key): mixed
    {
        if (! isset($this->Variables[$key])) {
            throw new \InvalidArgumentException('Provided key "' . $key . '" does not exist in this container.');
        }

        return $this->Variables[$key];
    }
}
