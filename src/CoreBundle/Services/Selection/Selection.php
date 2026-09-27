<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Selection;

class Selection
{
    /**
     * @param string[] $List
     * @param mixed $CurrentKey
     */
    public function __construct(
        protected array $List,
        protected $CurrentKey = null
    ) {
    }

    /**
     * @return string[]
     */
    public function getList(): array
    {
        return $this->List;
    }

    /**
     * @param mixed $key
     */
    public function setCurrentKey($key): void
    {
        $this->CurrentKey = $key;
    }

    /**
     * @return mixed
     */
    public function getCurrentKey()
    {
        return $this->CurrentKey;
    }

    public function hasCurrentKey(): bool
    {
        return isset($this->List[$this->CurrentKey]);
    }

    public function getCurrentLabel(): ?string
    {
        if ($this->hasCurrentKey()) {
            return $this->List[$this->CurrentKey];
        }

        $firstLabel = reset($this->List);

        return $firstLabel === false ? null : $firstLabel;
    }
}
