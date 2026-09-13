<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskInterface;

class StartTimeCalculation implements RecalculationTaskInterface
{
    use AccountAwareTaskTrait;

    /**
     * @var TrainingRepository
     */
    protected $TrainingRepository;

    /**
     * @var ConfigurationUpdater
     */
    protected $ConfigurationUpdater;

    /**
     * @var bool
     */
    protected $ForceRecalculation = false;

    /**
     * @var int|null
     */
    protected $NewStartTime;

    public function __construct(TrainingRepository $repository, ConfigurationUpdater $updater)
    {
        $this->TrainingRepository = $repository;
        $this->ConfigurationUpdater = $updater;
    }

    public function forceRecalculation(): void
    {
        $this->ForceRecalculation = true;
    }

    /**
     * @param int $timestamp
     */
    public function setNewStartTime($timestamp): void
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

        $this->ConfigurationUpdater->updateStartTime($this->Account, $this->NewStartTime);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
