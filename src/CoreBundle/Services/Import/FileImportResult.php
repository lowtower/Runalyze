<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

class FileImportResult
{
    /**
     * @var ActivityDataContainer[]
     */
    protected $Container;

    /**
     * @param ActivityDataContainer[] $container
     * @param string $FileName
     * @param string $OriginalFileName
     */
    public function __construct(
        array $container,
        protected $FileName,
        protected $OriginalFileName,
        protected ?\Exception $Exception = null
    ) {
        $this->Container = $container;
    }

    /**
     * @param int|null $index
     * @return ActivityDataContainer|ActivityDataContainer[]
     */
    public function getContainer($index = null)
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

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->FileName;
    }

    /**
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->OriginalFileName;
    }

    public function isFailed(): bool
    {
        return $this->Exception instanceof \Exception;
    }

    public function getException(): ?\Exception
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

        if ($lastException instanceof \Runalyze\Import\Exception\ParserException) {
            $this->Exception = $lastException;

            foreach ($indicesToRemove as $index) {
                unset($this->Container[$index]);
            }

            $this->Container = array_values($this->Container);
        }
    }
}
