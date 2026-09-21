<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskInterface;
use Runalyze\Util\LocalTime;

class VO2maxShapeCalculation implements RecalculationTaskInterface
{
    use AccountAwareTaskTrait;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\TrainingRepository $TrainingRepository,
        protected \Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager $ConfigurationManager,
        protected \Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater $ConfigurationUpdater
    ) {
    }

    public function run(): void
    {
        if ($this->Account === null) {
            return;
        }

        $configList = $this->ConfigurationManager->getList($this->Account);
        $timestamp = LocalTime::fromServerTime(time())->setTime(23, 59, 59)->getTimestamp();
        $shape = $this->TrainingRepository->calculateVO2maxShape(
            $this->Account,
            $configList->getVO2max(),
            $configList->getGeneral()
                ->getRunningSport(),
            $timestamp
        );

        $this->ConfigurationUpdater->updateVO2maxShape($this->Account, $shape * $configList->getVO2maxCorrectionFactor());
    }

    public function getOrder(): int
    {
        return 3;
    }
}
