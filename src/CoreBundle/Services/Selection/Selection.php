<?php

namespace Runalyze\Bundle\CoreBundle\Services\Selection;

class Selection
{
    /**
     * @var string[]
     */
    protected $List;

    /**
     * @param string[] $list
     * @param mixed $CurrentKey
     */
    public function __construct(
        array $list,
        protected $CurrentKey = null
    ) {
        $this->List = $list;
    }

    /**
     * @return string[]
     */
    public function getList()
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

    /**
     * @return string
     */
    public function getCurrentLabel()
    {
        if ($this->hasCurrentKey()) {
            return $this->List[$this->CurrentKey];
        }

        return reset($this->List);
    }
}
