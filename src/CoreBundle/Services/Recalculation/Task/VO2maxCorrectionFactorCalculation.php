<?php

namespace Runalyze\Bundle\CoreBundle\Services\Recalculation\Task;

use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskInterface;

class VO2maxCorrectionFactorCalculation implements RecalculationTaskInterface
{
    use AccountAwareTaskTrait;

    public function __construct(
        protected RaceresultRepository $RaceResultRepository,
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
        $factor = $this->RaceResultRepository->getEffectiveVO2maxCorrectionFactor(
            $this->Account,
            $configList->getGeneral()
                ->getRunningSport()
        );

        $this->ConfigurationUpdater->updateVO2maxCorrectionFactor($this->Account, $factor);
    }

    public function getOrder(): int
    {
        return 2;
    }
}
