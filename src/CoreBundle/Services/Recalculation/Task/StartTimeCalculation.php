<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskInterface;

class StartTimeCalculation implements RecalculationTaskInterface
{
    use AccountAwareTaskTrait;

    protected bool $ForceRecalculation = false;

    protected ?int $NewStartTime = null;

    public function __construct(
        protected TrainingRepository $TrainingRepository,
        protected ConfigurationUpdater $ConfigurationUpdater
    ) {
    }

    public function forceRecalculation(): void
    {
        $this->ForceRecalculation = true;
    }

    public function setNewStartTime(int $timestamp): void
    {
        if ($this->NewStartTime === null || $timestamp < $this->NewStartTime) {
            $this->NewStartTime = $timestamp;
        }
    }

    public function run(): void
    {
        if ($this->Account === null) {
            return;
        }

        if ($this->ForceRecalculation || $this->NewStartTime === null) {
            $this->NewStartTime = $this->TrainingRepository->getStartTime($this->Account);
        }

        if ($this->NewStartTime === null) {
            return;
        }

        $this->ConfigurationUpdater->updateStartTime($this->Account, $this->NewStartTime);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
