<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb;

class ClimbCollection implements \Countable, \ArrayAccess
{
    /**
     * @param Climb[] $Elements
     */
    public function __construct(
        protected array $Elements = []
    ) {
    }

    public function add(Climb $climb): void
    {
        $this->Elements[] = $climb;
    }

    /**
     * @return Climb[]
     */
    public function getElements(): array
    {
        return $this->Elements;
    }

    public function isEmpty(): bool
    {
        return $this->Elements === [];
    }

    public function count(): int
    {
        return count($this->Elements);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->Elements[$offset]);
    }

    public function offsetGet(mixed $offset): Climb
    {
        return $this->Elements[$offset];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->Elements[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->Elements[$offset]);
    }
}
