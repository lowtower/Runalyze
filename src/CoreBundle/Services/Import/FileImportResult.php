<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Exception;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

class FileImportResult
{
    /**
     * @param ActivityDataContainer[] $Container
     */
    public function __construct(
        protected array $Container,
        protected string $FileName,
        protected string $OriginalFileName,
        protected ?Exception $Exception = null
    ) {
    }

    /**
     * @return ActivityDataContainer|ActivityDataContainer[]
     */
    public function getContainer(?int $index = null): ActivityDataContainer|array
    {
        if ($index !== null && isset($this->Container[$index])) {
            return $this->Container[$index];
        }

        return $this->Container;
    }

    public function getNumberOfActivities(): int
    {
        if ($this->isFailed()) {
            return 0;
        }

        return count($this->Container);
    }

    public function getFileName(): string
    {
        return $this->FileName;
    }

    public function getOriginalFileName(): string
    {
        return $this->OriginalFileName;
    }

    public function isFailed(): bool
    {
        return $this->Exception instanceof Exception;
    }

    public function getException(): ?Exception
    {
        return $this->Exception;
    }

    public function completeAndFilterContainer(ActivityDataContainerFilter $filter): void
    {
        if ($this->isFailed()) {
            return;
        }

        $indicesToRemove = [];
        $lastException = null;

        foreach ($this->Container as $index => $container) {
            $container->completeContinuousData();

            try {
                $filter->filter($container);
            } catch (ParserException $e) {
                $indicesToRemove[] = $index;
                $lastException = $e;

                continue;
            }

            $container->completeActivityData();
        }

        if ($lastException instanceof ParserException) {
            $this->Exception = $lastException;

            foreach ($indicesToRemove as $index) {
                unset($this->Container[$index]);
            }

            $this->Container = array_values($this->Container);
        }
    }
}
