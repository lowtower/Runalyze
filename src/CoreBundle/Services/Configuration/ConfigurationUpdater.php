<?php

namespace Runalyze\Bundle\CoreBundle\Services\Configuration;

use Runalyze\Bundle\CoreBundle\Entity\Account;

class ConfigurationUpdater
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\ConfRepository $Repository,
        protected ?\Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager $Manager = null
    ) {
    }

    /**
     * @param string $configName
     */
    protected function updateInDataConfiguration(Account $account, $configName, string $value)
    {
        $this->Repository->updateOrInsert($account, 'data', $configName, $value);
        $this->Manager->getList($account)
            ->set('data.' . $configName, $value);
    }

    /**
     * @param int $startTime
     */
    public function updateStartTime(Account $account, $startTime): void
    {
        $this->updateInDataConfiguration($account, 'START_TIME', (string) $startTime);
    }

    /**
     * @param int $bpm
     */
    public function updateMaximalHeartRate(Account $account, $bpm): void
    {
        $this->updateInDataConfiguration($account, 'HF_MAX', (string) $bpm);
    }

    /**
     * @param int $bpm
     */
    public function updateRestingHeartRate(Account $account, $bpm): void
    {
        $this->updateInDataConfiguration($account, 'HF_REST', (string) $bpm);
    }

    /**
     * @param float $shape [0.00, inf)
     */
    public function updateVO2maxShape(Account $account, $shape): void
    {
        $this->updateInDataConfiguration($account, 'VO2MAX_FORM', (string) $shape);
    }

    /**
     * @param float $factor [0.00 .. 2.00]
     */
    public function updateVO2maxCorrectionFactor(Account $account, $factor): void
    {
        $this->updateInDataConfiguration($account, 'VO2MAX_CORRECTOR', (string) $factor);
    }

    /**
     * @param int $shape [0, inf)
     */
    public function updateMarathonShape(Account $account, $shape): void
    {
        $this->updateInDataConfiguration($account, 'BASIC_ENDURANCE', (string) $shape);
    }

    /**
     * @param int $atl [0, inf)
     */
    public function updateMaximalAtl(Account $account, $atl): void
    {
        $this->updateInDataConfiguration($account, 'MAX_ATL', (string) $atl);
    }

    /**
     * @param int $ctl [0, inf)
     */
    public function updateMaximalCtl(Account $account, $ctl): void
    {
        $this->updateInDataConfiguration($account, 'MAX_CTL', (string) $ctl);
    }

    /**
     * @param int $trimp [0, inf)
     */
    public function updateMaximalTrimp(Account $account, $trimp): void
    {
        $this->updateInDataConfiguration($account, 'MAX_TRIMP', (string) $trimp);
    }

    public function updatePrivacyDetails(Account $account, array $values): void
    {
        $list = $this->Manager->getList($account);

        foreach ($values as $key => $value) {
            $this->Repository->updateOrInsert($account, 'privacy', $key, $value);
            $list->set('privacy.' . $key, $value);
        }
    }
}
