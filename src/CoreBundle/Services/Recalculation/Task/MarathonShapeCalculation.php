<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskInterface;
use Runalyze\Util\LocalTime;

class MarathonShapeCalculation implements RecalculationTaskInterface
{
    use AccountAwareTaskTrait;

    public function __construct(
        protected TrainingRepository $TrainingRepository,
        protected ConfigurationManager $ConfigurationManager,
        protected ConfigurationUpdater $ConfigurationUpdater
    ) {
    }

    public function run(): void
    {
        if ($this->Account === null) {
            return;
        }

        $configList = $this->ConfigurationManager->getList($this->Account);
        $timestamp = LocalTime::fromServerTime(time())->setTime(23, 59, 59)->getTimestamp();
        $shape = $this->TrainingRepository->calculateMarathonShape(
            $this->Account,
            $configList->getBasicEndurance(),
            $configList->getCurrentVO2maxShape(),
            $configList->getGeneral()
                ->getRunningSport(),
            $timestamp,
            $configList->getData()
                ->getStartTime()
        );

        $this->ConfigurationUpdater->updateMarathonShape($this->Account, $shape);
    }

    public function getOrder(): int
    {
        return 4;
    }
}
