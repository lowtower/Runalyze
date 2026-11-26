<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;

final readonly class RechenspieleShapePlotMaximaUpdater
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private ConfigurationUpdater $configurationUpdater
    ) {}

    /**
     * @param array{atl: int, ctl: int}|null $recomputedMaxima
     */
    public function update(Account $account, ?array $recomputedMaxima): void
    {
        if ($recomputedMaxima === null) {
            return;
        }

        $configuration = $this->configurationManager->getList($account);

        if ($recomputedMaxima['atl'] !== (int) $configuration->get('data.MAX_ATL')) {
            $this->configurationUpdater->updateMaximalAtl($account, $recomputedMaxima['atl']);
        }

        if ($recomputedMaxima['ctl'] !== (int) $configuration->get('data.MAX_CTL')) {
            $this->configurationUpdater->updateMaximalCtl($account, $recomputedMaxima['ctl']);
        }
    }
}
