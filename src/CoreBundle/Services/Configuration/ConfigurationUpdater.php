<?php

namespace Runalyze\Bundle\CoreBundle\Services\Configuration;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\ConfRepository;

class ConfigurationUpdater
{
    public function __construct(
        protected ConfRepository $Repository,
        protected ConfigurationManager $Manager
    ) {}

    protected function updateInCategoryConfiguration(Account $account, string $category, string $key, string $value): void
    {
        $this->Repository->updateOrInsert($account, $category, $key, $value);
        $this->Manager->getList($account)
            ->set($category . '.' . $key, $value);
    }

    protected function updateInDataConfiguration(Account $account, string $configName, string $value): void
    {
        $this->updateInCategoryConfiguration($account, 'data', $configName, $value);
    }

    public function updateStartTime(Account $account, int $startTime): void
    {
        $this->updateInDataConfiguration($account, 'START_TIME', (string) $startTime);
    }

    public function updateMaximalHeartRate(Account $account, int $bpm): void
    {
        $this->updateInDataConfiguration($account, 'HF_MAX', (string) $bpm);
    }

    public function updateRestingHeartRate(Account $account, int $bpm): void
    {
        $this->updateInDataConfiguration($account, 'HF_REST', (string) $bpm);
    }

    /**
     * @param float $shape [0.00, inf)
     */
    public function updateVO2maxShape(Account $account, float $shape): void
    {
        $this->updateInDataConfiguration($account, 'VO2MAX_FORM', (string) $shape);
    }

    /**
     * @param float $factor [0.00 .. 2.00]
     */
    public function updateVO2maxCorrectionFactor(Account $account, float $factor): void
    {
        $this->updateInDataConfiguration($account, 'VO2MAX_CORRECTOR', (string) $factor);
    }

    /**
     * @param int $shape [0, inf)
     */
    public function updateMarathonShape(Account $account, int $shape): void
    {
        $this->updateInDataConfiguration($account, 'BASIC_ENDURANCE', (string) $shape);
    }

    /**
     * @param int $atl [0, inf)
     */
    public function updateMaximalAtl(Account $account, int $atl): void
    {
        $this->updateInDataConfiguration($account, 'MAX_ATL', (string) $atl);
    }

    /**
     * @param int $ctl [0, inf)
     */
    public function updateMaximalCtl(Account $account, int $ctl): void
    {
        $this->updateInDataConfiguration($account, 'MAX_CTL', (string) $ctl);
    }

    /**
     * @param int $trimp [0, inf)
     */
    public function updateMaximalTrimp(Account $account, int $trimp): void
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

    public function addIgnoredActivityId(Account $account, string $activityId): void
    {
        $category = $this->Manager->getList($account)->getActivityForm();
        $category->addIgnoredActivityId($activityId);

        $this->updateInCategoryConfiguration($account, 'activity-form', 'GARMIN_IGNORE_IDS', $category->getIgnoredActivityIdsAsString());
    }

    public function updateMapLayer(Account $account, string $layer): void
    {
        $this->updateInCategoryConfiguration($account, 'activity-view', 'TRAINING_LEAFLET_LAYER', $layer);
    }

    public function updateFieldVisibility(Account $account, string $legendKey, string $value): void
    {
        $this->updateInCategoryConfiguration($account, 'activity-form', 'FORMULAR_SHOW_' . $legendKey, $value);
    }

    public function resetToDefaults(Account $account): void
    {
        $this->Repository->deleteAllForAccountExceptGeneralAndData($account);
        $this->Manager->reloadCurrentList();
    }
}
