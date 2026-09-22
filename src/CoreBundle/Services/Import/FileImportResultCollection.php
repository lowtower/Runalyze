<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

/**
 * @implements \ArrayAccess<int, FileImportResult>
 * @implements \Iterator<int, FileImportResult>
 */
class FileImportResultCollection implements \Countable, \ArrayAccess, \Iterator
{
    /**
     * @var FileImportResult[]
     */
    protected array $Elements = [];

    protected int $CurrentOffset = 0;

    /**
     * @param FileImportResult[] $elements
     */
    public function __construct(array $elements = [])
    {
        foreach ($elements as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }

    public function merge(self $other): void
    {
        $this->Elements = array_merge($this->Elements, $other->getElements());
    }

    public function add(FileImportResult $result): void
    {
        $this->Elements[] = $result;
    }

    /**
     * @return FileImportResult[]
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

    /**
     * @param int $offset
     * @return FileImportResult
     */
    public function offsetGet($offset): mixed
    {
        return $this->Elements[$offset];
    }

    /**
     * @param ?int $offset
     * @param FileImportResult $value
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value): void
    {
        if (! ($value instanceof FileImportResult)) {
            throw new \InvalidArgumentException('FileImportResultCollection does only accept instances of FileImportResult as elements.');
        }

        if ($offset === null) {
            $this->Elements[] = $value;
        } else {
            $this->Elements[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->Elements[$offset]);
    }

    public function current(): mixed
    {
        return $this->Elements[$this->CurrentOffset];
    }

    public function key(): mixed
    {
        return $this->CurrentOffset;
    }

    public function next(): void
    {
        ++$this->CurrentOffset;
    }

    public function rewind(): void
    {
        $this->CurrentOffset = 0;
    }

    public function valid(): bool
    {
        return isset($this->Elements[$this->CurrentOffset]);
    }

    public function reindex(): void
    {
        $this->Elements = array_values($this->Elements);
    }

    public function getTotalNumberOfActivities(): int
    {
        return array_reduce(
            $this->Elements,
            fn ($num, FileImportResult $result): int => $num + $result->getNumberOfActivities(),
            0
        );
    }

    /**
     * @return string[]
     */
    public function getAllFileNames(): array
    {
        return array_map(function (FileImportResult $element) {
            /** @var FileImportResult $element */
            return $element->getFileName();
        }, $this->Elements);
    }

    /**
     * @return string[]
     */
    public function getAllOriginalFileNames(): array
    {
        return array_values(
            array_unique(
                array_map(function (FileImportResult $element) {
                    /** @var FileImportResult $element */
                    return $element->getOriginalFileName();
                }, $this->Elements)
            )
        );
    }

    /**
     * @return string[]
     */
    public function getAllConvertedFileNames(): array
    {
        $convertedFiles = [];

        foreach ($this->Elements as $element) {
            if ($element->getOriginalFileName() != $element->getFileName()) {
                $convertedFiles[] = $element->getFileName();
            }
        }

        return $convertedFiles;
    }

    public function completeAndFilterResults(ActivityDataContainerFilter $filter): void
    {
        foreach ($this->Elements as $element) {
            $element->completeAndFilterContainer($filter);
        }
    }
}
