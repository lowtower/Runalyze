<?php

namespace Runalyze\Model\Common;

trait WithNullableArraysTrait
{
    public function ensureArraysToBeNotNull(): void
    {
        foreach (static::allDatabaseProperties() as $key) {
            if ($this->isArray($key) && $this->get($key) === null) {
                $this->set($key, []);
            }
        }
    }

    public function ensureArraysToBeNullIfEmpty(): void
    {
        foreach (static::allDatabaseProperties() as $key) {
            if ($this->isArray($key) && empty($this->get($key))) {
                $this->set($key, null);
            }
        }
    }
}
