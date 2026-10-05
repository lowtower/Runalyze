<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation;

class RecalculationTaskCollection
{
    /**
     * @var RecalculationTaskInterface[]
     */
    protected array $Tasks = [];

    protected int $NextTaskIndex = 0;

    public function addTask(RecalculationTaskInterface $task): int
    {
        $this->Tasks[] = $task;

        return $this->NextTaskIndex++;
    }

    public function runAllTasks(): void
    {
        usort($this->Tasks, fn (RecalculationTaskInterface $a, RecalculationTaskInterface $b): int => $a->getOrder() <=> $b->getOrder());

        foreach ($this->Tasks as $task) {
            $task->run();
        }
    }

    public function offsetGet(int $offset): RecalculationTaskInterface
    {
        return $this->Tasks[$offset];
    }

    public function offsetUnset(int $offset): void
    {
        unset($this->Tasks[$offset]);
    }

    public function clear(): void
    {
        $this->NextTaskIndex = 0;
        $this->Tasks = [];
    }
}
